<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\MergeLexemes;

use MediaWiki\Context\IContextSource;
use MediaWiki\Permissions\PermissionManager;
use WatchedItemStoreInterface;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirector;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeLoadingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeNotFoundException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeSaveFailedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\PermissionDeniedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class MergeLexemesInteractor {

	private SummaryFormatter $summaryFormatter;
	private EntityRevisionLookup $entityRevisionLookup;
	private MediaWikiLexemeRedirector $lexemeRedirector;
	private EntityPermissionChecker $permissionChecker;
	private PermissionManager $permissionManager;
	private EntityTitleStoreLookup $entityTitleLookup;
	private LexemeMerger $lexemeMerger;
	private WatchedItemStoreInterface $watchedItemStore;
	private MediaWikiEditEntityFactory $editEntityFactory;

	public function __construct(
		LexemeMerger $lexemeMerger,
		SummaryFormatter $summaryFormatter,
		MediaWikiLexemeRedirector $lexemeRedirector,
		EntityPermissionChecker $permissionChecker,
		PermissionManager $permissionManager,
		EntityTitleStoreLookup $entityTitleLookup,
		WatchedItemStoreInterface $watchedItemStore,
		EntityRevisionLookup $entityRevisionLookup,
		MediaWikiEditEntityFactory $editEntityFactory
	) {
		$this->lexemeMerger = $lexemeMerger;
		$this->summaryFormatter = $summaryFormatter;
		$this->lexemeRedirector = $lexemeRedirector;
		$this->permissionChecker = $permissionChecker;
		$this->permissionManager = $permissionManager;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->watchedItemStore = $watchedItemStore;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
	}

	/**
	 * @param LexemeId $sourceId
	 * @param LexemeId $targetId
	 * @param string|null $summary - only relevant when called through the API
	 * @param string[] $tags
	 *
	 * @return MergeLexemesStatus Note that the status is only returned
	 * to wrap the context and saved temp user in a strongly typed container.
	 * Errors are (currently) reported as exceptions, not as a failed status.
	 * (It would be nice to fix this at some point and use status consistently.)
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
	): MergeLexemesStatus {
		$this->checkCanMerge( $sourceId, $context );
		$this->checkCanMerge( $targetId, $context );

		$source = $this->getLexeme( $sourceId );
		$target = $this->getLexeme( $targetId );

		$this->validateEntities( $source, $target );

		$this->lexemeMerger->merge( $source, $target );

		$mergeStatus = $this->attemptSaveMerge( $source, $target, $context, $summary, $botEditRequested, $tags );
		$context = $mergeStatus->getContext();
		$this->updateWatchlistEntries( $sourceId, $targetId );

		$redirectStatus = $this->lexemeRedirector
			->createRedirect( $sourceId, $targetId, $botEditRequested, $tags, $context );
		$context = $redirectStatus->getContext();

		return MergeLexemesStatus::newMerge(
			$mergeStatus->getSavedTempUser() ?? $redirectStatus->getSavedTempUser(),
			$context
		);
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
	private function getLexeme( LexemeId $lexemeId ): Lexeme {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				0,
				LookupConstants::LATEST_FROM_MASTER
			);

			if ( $revision ) {
				// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
				return $revision->getEntity();
			} else {
				throw new LexemeNotFoundException( $lexemeId );
			}
		} catch ( StorageException | RevisionedUnresolvedRedirectException $ex ) {
			throw new LexemeLoadingException();
		}
	}

	/**
	 * @throws ReferenceSameLexemeException
	 */
	private function validateEntities( EntityDocument $fromEntity, EntityDocument $toEntity ): void {
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
	private function getSummary(
		string $direction,
		LexemeId $id,
		?string $customSummary = null
	): Summary {
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
	): MergeLexemesStatus {
		$toResult = $this->saveLexeme(
			$source,
			$context,
			$this->getSummary( 'to', $target->getId(), $summary ),
			$botEditRequested,
			$tags
		);
		$context = $toResult->getContext();

		$fromResult = $this->saveLexeme(
			$target,
			$context,
			$this->getSummary( 'from', $source->getId(), $summary ),
			$botEditRequested,
			$tags
		);
		$context = $fromResult->getContext();

		return MergeLexemesStatus::newMerge(
			$fromResult->getSavedTempUser() ?? $toResult->getSavedTempUser(),
			$context
		);
	}

	private function saveLexeme(
		Lexeme $lexeme,
		IContextSource $context,
		FormatableSummary $summary,
		bool $botEditRequested,
		array $tags
	): EditEntityStatus {
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

		return $status;
	}

	private function updateWatchlistEntries( LexemeId $fromId, LexemeId $toId ): void {
		$this->watchedItemStore->duplicateAllAssociatedEntries(
			$this->entityTitleLookup->getTitleForId( $fromId ),
			$this->entityTitleLookup->getTitleForId( $toId )
		);
	}

}
