<?php

namespace Wikibase\Lexeme\Interactors\MergeLexemes;

use IContextSource;
use MediaWiki\Permissions\PermissionManager;
use WatchedItemStoreInterface;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirectorFactory;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRepositoryFactory;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeLoadingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeNotFoundException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeSaveFailedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\PermissionDeniedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Storage\GetLexemeException;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class MergeLexemesInteractor {

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var MediaWikiLexemeRepositoryFactory
	 */
	private $repoFactory;

	/**
	 * @var MediaWikiLexemeRedirectorFactory
	 */
	private $lexemeRedirectorFactory;

	private EntityPermissionChecker $permissionChecker;

	private PermissionManager $permissionManager;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var LexemeMerger
	 */
	private $lexemeMerger;

	/**
	 * @var WatchedItemStoreInterface
	 */
	private $watchedItemStore;

	private MediaWikiEditEntityFactory $editEntityFactory;

	public function __construct(
		LexemeMerger $lexemeMerger,
		SummaryFormatter $summaryFormatter,
		MediaWikiLexemeRedirectorFactory $lexemeRedirectorFactory,
		EntityPermissionChecker $permissionChecker,
		PermissionManager $permissionManager,
		EntityTitleStoreLookup $entityTitleLookup,
		WatchedItemStoreInterface $watchedItemStore,
		MediaWikiLexemeRepositoryFactory $repoFactory,
		MediaWikiEditEntityFactory $editEntityFactory
	) {
		$this->lexemeMerger = $lexemeMerger;
		$this->summaryFormatter = $summaryFormatter;
		$this->lexemeRedirectorFactory = $lexemeRedirectorFactory;
		$this->permissionChecker = $permissionChecker;
		$this->permissionManager = $permissionManager;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->watchedItemStore = $watchedItemStore;
		$this->repoFactory = $repoFactory;
		$this->editEntityFactory = $editEntityFactory;
	}

	/**
	 * @param LexemeId $sourceId
	 * @param LexemeId $targetId
	 * @param string|null $summary - only relevant when called through the API
	 * @param string[] $tags
	 *
	 * @throws MergingException
	 */
	public function mergeLexemes(
		LexemeId $sourceId,
		LexemeId $targetId,
		IContextSource $context,
		?string $summary = null,
		bool $botEditRequested = false,
		array $tags = []
	) {
		$this->checkCanMerge( $sourceId, $context );
		$this->checkCanMerge( $targetId, $context );

		$repo = $this->repoFactory->newFromContext( $context, $botEditRequested, $tags );

		// TODO replace repo with an EntityLookup
		$source = $this->getLexeme( $repo, $sourceId );
		$target = $this->getLexeme( $repo, $targetId );

		$this->validateEntities( $source, $target );

		$this->lexemeMerger->merge( $source, $target );

		$this->attemptSaveMerge( $source, $target, $context, $summary, $botEditRequested, $tags );
		$this->updateWatchlistEntries( $sourceId, $targetId );

		$this->lexemeRedirectorFactory
			->newFromContext( $context, $botEditRequested, $tags )
			->redirect( $sourceId, $targetId );
	}

	private function checkCanMerge( LexemeId $lexemeId, IContextSource $context ): void {
		$status = $this->permissionChecker->getPermissionStatusForEntityId(
			$context->getUser(),
			EntityPermissionChecker::ACTION_MERGE,
			$lexemeId
		);

		if ( !$status->isOK() ) {
			// would be nice to propagate the errors from $status...
			throw new PermissionDeniedException();
		}
	}

	/**
	 * @throws MergingException
	 */
	private function getLexeme( LexemeRepository $repo, LexemeId $lexemeId ): Lexeme {
		try {
			$lexeme = $repo->getLexemeById( $lexemeId );
		} catch ( GetLexemeException $ex ) {
			throw new LexemeLoadingException();
		}

		if ( $lexeme === null ) {
			throw new LexemeNotFoundException( $lexemeId );
		}

		return $lexeme;
	}

	/**
	 * @param EntityDocument $fromEntity
	 * @param EntityDocument $toEntity
	 *
	 * @throws ReferenceSameLexemeException
	 */
	private function validateEntities( EntityDocument $fromEntity, EntityDocument $toEntity ) {
		if ( $toEntity->getId()->equals( $fromEntity->getId() ) ) {
			throw new ReferenceSameLexemeException();
		}
	}

	/**
	 * @param string $direction either 'from' or 'to'
	 * @param LexemeId $id
	 * @param string|null $customSummary
	 *
	 * @return Summary
	 */
	private function getSummary( $direction, LexemeId $id, $customSummary = null ) {
		$summary = new Summary( 'wblmergelexemes', $direction, null, [ $id->getSerialization() ] );
		$summary->setUserSummary( $customSummary );

		return $summary;
	}

	private function attemptSaveMerge(
		Lexeme $source,
		Lexeme $target,
		IContextSource $context,
		?string $summary,
		bool $botEditRequested,
		array $tags
	) {
		$this->saveLexeme(
			$source,
			$context,
			$this->getSummary( 'to', $target->getId(), $summary ),
			$botEditRequested,
			$tags
		);

		$this->saveLexeme(
			$target,
			$context,
			$this->getSummary( 'from', $source->getId(), $summary ),
			$botEditRequested,
			$tags
		);
	}

	private function saveLexeme(
		Lexeme $lexeme,
		IContextSource $context,
		FormatableSummary $summary,
		bool $botEditRequested,
		array $tags
	) {
		// TODO: the EntityContent::EDIT_IGNORE_CONSTRAINTS flag does not seem to be used by Lexeme
		// (LexemeHandler has no onSaveValidators)
		$flags = EDIT_UPDATE | EntityContent::EDIT_IGNORE_CONSTRAINTS;
		if ( $botEditRequested && $this->permissionManager->userHasRight( $context->getUser(), 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$formattedSummary = $this->summaryFormatter->formatSummary( $summary );

		$editEntity = $this->editEntityFactory->newEditEntity( $context, $lexeme->getId() );
		$status = $editEntity->attemptSave(
			$lexeme,
			$formattedSummary,
			$flags,
			false,
			null,
			$tags
		);
		if ( !$status->isOK() ) {
			throw new LexemeSaveFailedException( $status->getWikiText() );
		}
	}

	private function updateWatchlistEntries( LexemeId $fromId, LexemeId $toId ) {
		$this->watchedItemStore->duplicateAllAssociatedEntries(
			$this->entityTitleLookup->getTitleForId( $fromId ),
			$this->entityTitleLookup->getTitleForId( $toId )
		);
	}

}
