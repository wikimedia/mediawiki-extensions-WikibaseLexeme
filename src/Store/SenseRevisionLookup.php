<?php

namespace Wikibase\Lexeme\Store;

use OutOfRangeException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataTransfer\NullSenseId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class SenseRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	public function __construct( EntityRevisionLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @param SenseId $senseId
	 * @param int $revisionId
	 * @param string $mode
	 *
	 * @throws UnexpectedValueException
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $senseId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_REPLICA
	) {
		Assert::parameterType( SenseId::class, $senseId, '$senseId' );

		if ( $senseId instanceof NullSenseId ) {
			return null;
		}

		$revision = $this->lookup->getEntityRevision( $senseId->getLexemeId(), $revisionId, $mode );
		if ( $revision === null ) {
			return null;
		}

		/** @var Lexeme $lexeme */
		$lexeme = $revision->getEntity();

		try {
			// TODO use hasSense on Lexeme or SenseSet when it exists
			$sense = $lexeme->getSense( $senseId );
		} catch ( OutOfRangeException $ex ) {
			return null;
		}

		return new EntityRevision( $sense, $revision->getRevisionId(), $revision->getTimestamp() );
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param SenseId $senseId
	 * @param string $mode
	 *
	 * @throws UnexpectedValueException
	 * @return LatestRevisionIdResult|int|false
	 */
	public function getLatestRevisionId( EntityId $senseId, $mode = self::LATEST_FROM_REPLICA ) {
		Assert::parameterType( SenseId::class, $senseId, '$senseId' );

		$lexemeId = $senseId->getLexemeId();
		$revisionId = $this->lookup->getLatestRevisionId( $lexemeId, $mode );

		if (
			class_exists( LatestRevisionIdResult::class )
			&& is_object( $revisionId )
			&& $revisionId instanceof LatestRevisionIdResult
		) {
			return $this->handleNewResult( $senseId, $mode, $revisionId, $lexemeId );
		}

		//TODO Remove everything below once patch in Wikibase is merged
		if ( $revisionId === false ) {
			return false;
		}

		$revision = $this->lookup->getEntityRevision( $lexemeId, $revisionId, $mode );
		/** @var Lexeme $lexeme */
		$lexeme = $revision->getEntity();

		try {
			$lexeme->getSense( $senseId );
		} catch ( OutOfRangeException $ex ) {
			return false;
		}

		return $revisionId;
	}

	/**
	 * @param SenseId $senseId
	 * @param string $mode
	 * @param LatestRevisionIdResult $revisionIdResult
	 * @param LexemeId $lexemeId
	 * @return LatestRevisionIdResult
	 * @throws \Exception
	 */
	private function handleNewResult(
		SenseId $senseId,
		$mode,
		LatestRevisionIdResult $revisionIdResult,
		LexemeId $lexemeId
	) {
		$returnNonexistentEntityResult = function () {
			return LatestRevisionIdResult::nonexistentEntity();
		};

		return $revisionIdResult->onRedirect( $returnNonexistentEntityResult )
			->onNonexistentEntity( $returnNonexistentEntityResult )
			->onConcreteRevision(
				function ( $revisionId ) use ( $lexemeId, $mode, $senseId ) {
					$revision = $this->lookup->getEntityRevision( $lexemeId, $revisionId, $mode );
					/** @var Lexeme $lexeme */
					$lexeme = $revision->getEntity();

					try {
						$lexeme->getSense( $senseId );
					} catch ( OutOfRangeException $ex ) {
						return LatestRevisionIdResult::nonexistentEntity();
					}

					return LatestRevisionIdResult::concreteRevision( $revisionId );
				}
			)
			->map();
	}

}
