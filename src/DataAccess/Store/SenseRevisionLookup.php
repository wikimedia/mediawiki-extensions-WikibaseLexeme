<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use OutOfRangeException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\LookupConstants;
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
		$mode = LookupConstants::LATEST_FROM_REPLICA
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
		'@phan-var Lexeme $lexeme';

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
	public function getLatestRevisionId(
		EntityId $senseId,
		$mode = LookupConstants::LATEST_FROM_REPLICA
	) {
		Assert::parameterType( SenseId::class, $senseId, '$senseId' );

		$lexemeId = $senseId->getLexemeId();
		$revisionIdResult = $this->lookup->getLatestRevisionId( $lexemeId, $mode );

		$returnNonexistentEntityResult = static function () {
			return LatestRevisionIdResult::nonexistentEntity();
		};

		return $revisionIdResult->onRedirect( $returnNonexistentEntityResult )
			->onNonexistentEntity( $returnNonexistentEntityResult )
			->onConcreteRevision(
				function ( $revisionId, $revisionTimestamp ) use ( $lexemeId, $mode, $senseId ) {
					$revision = $this->lookup->getEntityRevision( $lexemeId, $revisionId, $mode );
					/** @var Lexeme $lexeme */
					$lexeme = $revision->getEntity();
					'@phan-var Lexeme $lexeme';

					try {
						$lexeme->getSense( $senseId );
					} catch ( OutOfRangeException $ex ) {
						return LatestRevisionIdResult::nonexistentEntity();
					}

					return LatestRevisionIdResult::concreteRevision( $revisionId, $revisionTimestamp );
				}
			)
			->map();
	}

}
