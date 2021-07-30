<?php

namespace Wikibase\Lexeme\Interactors\MergeLexemes;

use IContextSource;
use WatchedItemStoreInterface;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataAccess\Store\MediaWikiLexemeRedirectorFactory;
use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
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
use Wikibase\Lexeme\Domain\Storage\UpdateLexemeException;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class MergeLexemesInteractor {

	/**
	 * @var LexemeAuthorizer
	 */
	private $authorizer;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var LexemeRepository
	 */
	private $repo;

	/**
	 * @var MediaWikiLexemeRedirectorFactory
	 */
	private $lexemeRedirectorFactory;

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

	public function __construct(
		LexemeMerger $lexemeMerger,
		LexemeAuthorizer $authorizer,
		SummaryFormatter $summaryFormatter,
		MediaWikiLexemeRedirectorFactory $lexemeRedirectorFactory,
		EntityTitleStoreLookup $entityTitleLookup,
		WatchedItemStoreInterface $watchedItemStore,
		LexemeRepository $repo
	) {
		$this->lexemeMerger = $lexemeMerger;
		$this->authorizer = $authorizer;
		$this->summaryFormatter = $summaryFormatter;
		$this->lexemeRedirectorFactory = $lexemeRedirectorFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->watchedItemStore = $watchedItemStore;
		$this->repo = $repo;
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
		if ( !$this->authorizer->canMerge( $sourceId, $targetId ) ) {
			throw new PermissionDeniedException();
		}

		$source = $this->getLexeme( $sourceId );
		$target = $this->getLexeme( $targetId );

		$this->validateEntities( $source, $target );

		$this->lexemeMerger->merge( $source, $target );

		$this->attemptSaveMerge( $source, $target, $summary, $tags );
		$this->updateWatchlistEntries( $sourceId, $targetId );

		$this->lexemeRedirectorFactory
			->newFromContext( $context, $botEditRequested, $tags )
			->redirect( $sourceId, $targetId );
	}

	/**
	 * @throws MergingException
	 */
	private function getLexeme( LexemeId $lexemeId ): Lexeme {
		try {
			$lexeme = $this->repo->getLexemeById( $lexemeId );
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

	private function attemptSaveMerge( Lexeme $source, Lexeme $target, ?string $summary, array $tags ) {
		$this->saveLexeme(
			$source,
			$this->getSummary( 'to', $target->getId(), $summary ),
			$tags
		);

		$this->saveLexeme(
			$target,
			$this->getSummary( 'from', $source->getId(), $summary ),
			$tags
		);
	}

	private function saveLexeme( Lexeme $lexeme, FormatableSummary $summary, array $tags ) {

		try {
			$this->repo->updateLexeme(
				$lexeme,
				$this->summaryFormatter->formatSummary( $summary ),
				$tags
			);
		} catch ( UpdateLexemeException $ex ) {
			throw new LexemeSaveFailedException( $ex->getMessage(), $ex->getCode(), $ex );
		}
	}

	private function updateWatchlistEntries( LexemeId $fromId, LexemeId $toId ) {
		$this->watchedItemStore->duplicateAllAssociatedEntries(
			$this->entityTitleLookup->getTitleForId( $fromId ),
			$this->entityTitleLookup->getTitleForId( $toId )
		);
	}

}
