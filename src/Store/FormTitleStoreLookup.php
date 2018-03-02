<?php

namespace Wikibase\Lexeme\Store;

use Title;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

/**
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormTitleStoreLookup implements EntityTitleStoreLookup {

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $lookup;

	public function __construct( EntityTitleStoreLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityTitleStoreLookup::getTitleForId
	 *
	 * @param FormId $formId
	 *
	 * @throws UnexpectedValueException
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $formId ) {
		if ( !( $formId instanceof FormId ) ) {
			throw new UnexpectedValueException( '$formId must be a FormId' );
		}

		return $this->lookup->getTitleForId( $this->getLexemeId( $formId ) );
	}

	/**
	 * TODO: Move to the Form class.
	 *
	 * @param FormId $formId
	 *
	 * @return LexemeId
	 */
	private function getLexemeId( FormId $formId ) {
		$parts = EntityId::splitSerialization( $formId->getLocalPart() );
		$parts = explode( '-', $parts[2], 2 );
		return new LexemeId( $parts[0] );
	}

}
