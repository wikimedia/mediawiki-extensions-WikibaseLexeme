<?php

namespace Wikibase\Lexeme\Store;

use OutOfRangeException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class FormRevisionLookup implements EntityRevisionLookup {

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
	 * @param FormId $formId
	 * @param int $revisionId
	 * @param string $mode
	 *
	 * @throws UnexpectedValueException
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $formId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_REPLICA
	) {
		$lexemeId = $this->getLexemeId( $formId );
		$revision = $this->lookup->getEntityRevision( $lexemeId, $revisionId, $mode );
		if ( $revision === null ) {
			return null;
		}

		/** @var Lexeme $lexeme */
		$lexeme = $revision->getEntity();

		try {
			$form = $lexeme->getForm( $formId );
		} catch ( OutOfRangeException $ex ) {
			throw new BadRevisionException( "Revision $revisionId exists and holds $lexemeId, but "
				. "does not contain $formId", 0, $ex );
		}

		return new EntityRevision( $form, $revision->getRevisionId(), $revision->getTimestamp() );
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param FormId $formId
	 * @param string $mode
	 *
	 * @throws UnexpectedValueException
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $formId, $mode = self::LATEST_FROM_REPLICA ) {
		return $this->lookup->getLatestRevisionId( $this->getLexemeId( $formId ), $mode );
	}

	/**
	 * TODO: Move to the Form class.
	 *
	 * @param FormId $formId
	 *
	 * @return LexemeId
	 */
	private function getLexemeId( EntityId $formId ) {
		if ( !( $formId instanceof FormId ) ) {
			throw new UnexpectedValueException( '$formId must be a FormId' );
		}

		$parts = EntityId::splitSerialization( $formId->getLocalPart() );
		$parts = explode( '-', $parts[2], 2 );
		return new LexemeId( $parts[0] );
	}

}
