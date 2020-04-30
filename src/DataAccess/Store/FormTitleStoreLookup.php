<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use Title;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\DummyObjects\NullFormId;
use Wikibase\Lexeme\Domain\Model\FormId;
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

		if ( $formId instanceof NullFormId ) {
			return null;
		}

		$title = $this->lookup->getTitleForId( $formId->getLexemeId() );

		if ( $title === null ) {
			return null;
		}

		$title->setFragment( '#' . $formId->getIdSuffix() );

		return $title;
	}

	/**
	 * @inheritDoc
	 */
	public function getTitlesForIds( array $ids ) {
		$result = [];
		/** @var EntityId $id */
		foreach ( $ids as $id ) {
			$result[$id->getSerialization()] = $this->getTitleForId( $id );
		}

		return $result;
	}

}
