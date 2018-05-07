<?php

namespace Wikibase\Lexeme\Store;

use Title;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Assert\Assert;

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
		Assert::parameterType( FormId::class, $formId, '$formId' );

		$title = $this->lookup->getTitleForId( $formId->getLexemeId() );

		if ( $title === null ) {
			return null;
		}

		$title->setFragment( '#' . $formId->getSerialization() );

		return $title;
	}

}
