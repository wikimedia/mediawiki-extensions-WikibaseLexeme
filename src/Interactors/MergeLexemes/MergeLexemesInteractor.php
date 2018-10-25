<?php

namespace Wikibase\Lexeme\Interactors\MergeLexemes;

use WatchedItemStoreInterface;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeLoadingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeNotFoundException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeSaveFailedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\PermissionDeniedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeRedirectCreationInteractor;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lexeme\Domain\Storage\UpdateLexemeException;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class MergeLexemesInteractor {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

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
	 * @var LexemeRedirectCreationInteractor
	 */
	private $redirectInteractor;

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
		EntityRevisionLookup $entityRevisionLookup,
		LexemeAuthorizer $authorizer,
		SummaryFormatter $summaryFormatter,
		LexemeRedirectCreationInteractor $redirectInteractor,
		EntityTitleStoreLookup $entityTitleLookup,
		WatchedItemStoreInterface $watchedItemStore,
		LexemeRepository $repo
	) {
		$this->lexemeMerger = $lexemeMerger;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->authorizer = $authorizer;
		$this->summaryFormatter = $summaryFormatter;
		$this->redirectInteractor = $redirectInteractor;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->watchedItemStore = $watchedItemStore;
		$this->repo = $repo;
	}

	/**
	 * @param LexemeId $sourceId
	 * @param LexemeId $targetId
	 * @param string|null $summary - only relevant when called through the API
	 * @param bool $isBotEdit - only relevant when called through the API
	 *
	 * @throws MergingException
	 */
	public function mergeLexemes(
		LexemeId $sourceId,
		LexemeId $targetId,
		$summary = null,
		$isBotEdit = false
	) {
		if ( !$this->authorizer->canMerge( $sourceId, $targetId ) ) {
			throw new PermissionDeniedException();
		}

		/**
		 * @var Lexeme $source
		 * @var Lexeme $target
		 */
		$source = $this->loadEntity( $sourceId );
		$target = $this->loadEntity( $targetId );

		$this->validateEntities( $source, $target );

		$this->lexemeMerger->merge( $source, $target );

		$this->attemptSaveMerge( $source, $target, $summary );
		$this->updateWatchlistEntries( $sourceId, $targetId );

		$this->redirectInteractor->createRedirect( $sourceId, $targetId, $isBotEdit );
	}

	/**
	 * Either throws an exception or returns an EntityDocument object.
	 *
	 * @param LexemeId $lexemeId
	 *
	 * @return EntityDocument
	 *
	 * @throws MergingException
	 */
	private function loadEntity( LexemeId $lexemeId ): EntityDocument {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				0,
				EntityRevisionLookup::LATEST_FROM_MASTER
			);

			if ( !$revision ) {
				throw new LexemeNotFoundException();
			}

			return $revision->getEntity();
		} catch ( StorageException $ex ) {
			throw new LexemeLoadingException();
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			throw new LexemeLoadingException();
		}
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

	/**
	 * @param Lexeme $source
	 * @param Lexeme $target
	 * @param string|null $summary
	 */
	private function attemptSaveMerge( Lexeme $source, Lexeme $target, $summary ) {
		$this->saveLexeme(
			$source,
			$this->getSummary( 'to', $target->getId(), $summary )
		);

		$this->saveLexeme(
			$target,
			$this->getSummary( 'from', $source->getId(), $summary )
		);
	}

	private function saveLexeme( Lexeme $lexeme, FormatableSummary $summary ) {

		try {
			$this->repo->updateLexeme(
				$lexeme,
				$this->summaryFormatter->formatSummary( $summary )
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
