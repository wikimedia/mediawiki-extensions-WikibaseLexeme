<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use BadMethodCallException;
use LogicException;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;

/**
 * An Accessor for SubEntities that uses the supplied Accessor to look up
 * the Lexeme's MetaData. Only loadLatestRevisionIds and loadRevisionInformation
 * are currently implemented
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiPageSubEntityMetaDataAccessor implements WikiPageEntityMetaDataAccessor {

	private $wikiPageEntityMetaDataLookup;

	public function __construct( WikiPageEntityMetaDataAccessor $entityMetaDataAccessor ) {
		$this->wikiPageEntityMetaDataLookup = $entityMetaDataAccessor;
	}

	/**
	 * Loads revision information of entities that given ids of sub-entities (forms or senses)
	 * belong.
	 *
	 * @param EntityId[] $entityIds values must be instances of {@link LexemeSubEntityId}
	 * @param string $mode (LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @return (stdClass|bool)[] Array mapping entity ID serializations to either objects
	 * or false if an entity could not be found.
	 *
	 * @throws LogicException if some entity id in $entityIds is not instance of
	 * {@link LexemeSubEntityId}
	 */
	public function loadRevisionInformation( array $entityIds, $mode ) {
		$subEntityIds = [];
		foreach ( $entityIds as $key => $entityId ) {
			if ( $entityId instanceof LexemeSubEntityId ) {
				$subEntityIds[] = $entityId;
				$entityIds[$key] = $entityId->getLexemeId();
			} else {
				throw new LogicException(
					$entityId->getSerialization() . ' is not instance of ' . LexemeSubEntityId::class );
			}
		}

		$entitiesRevisionInfo = $this->wikiPageEntityMetaDataLookup->loadRevisionInformation(
			$entityIds,
			$mode
		);

		$subEntitiesRevisionInformation = [];
		/** @var LexemeSubEntityId $subEntityId */
		foreach ( $subEntityIds as $subEntityId ) {
			$subEntityIdString = $subEntityId->getSerialization();
			$lexemeIdString = $subEntityId->getLexemeId()->getSerialization();
			$subEntitiesRevisionInformation[ $subEntityIdString ] = $entitiesRevisionInfo[ $lexemeIdString ];
		}

		return $subEntitiesRevisionInformation;
	}

	/**
	 * Not implemented
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId Revision id to fetch data about, must be an integer greater than 0.
	 * @param string $mode (LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER).
	 *
	 * @return never
	 *
	 * @throws BadMethodCallException
	 */
	public function loadRevisionInformationByRevisionId( EntityId $entityId,
		$revisionId,
		$mode = LookupConstants::LATEST_FROM_MASTER ) {
		throw new BadMethodCallException( 'Not Implemented' );
	}

	/**
	 * Looks up the latest revision ID(s) for the given entityId(s).
	 * Returns an array of integer revision IDs using the wrapped
	 * WikiPageEntityMetaDataLookup
	 *
	 * If passed a LexemeSubEntityId then the look up if done on the lexeme
	 * and the SubEntity row is reinserted into the lookup afterwards.
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode (LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @return (int|bool)[] Array mapping entity ID serializations to either revision IDs
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	public function loadLatestRevisionIds( array $entityIds, $mode ): array {
		$subEntityIds = [];
		foreach ( $entityIds as $key => $entityId ) {
			if ( $entityId instanceof LexemeSubEntityId ) {
				$subEntityIds[] = $entityId;
				$entityIds[$key] = $entityId->getLexemeId();
			} else {
				throw new LogicException();
			}
		}

		$entityRevisionIds = $this->wikiPageEntityMetaDataLookup->loadLatestRevisionIds(
			$entityIds,
			$mode
		);

		$subEntitiesRevisionIds = [];
		/** @var LexemeSubEntityId $subEntityId */
		foreach ( $subEntityIds as $subEntityId ) {
			$subEntityIdString = $subEntityId->getSerialization();
			$lexemeIdString = $subEntityId->getLexemeId()->getSerialization();
			$subEntitiesRevisionIds[ $subEntityIdString ] = $entityRevisionIds[ $lexemeIdString ];
		}

		return $subEntitiesRevisionIds;
	}

}
