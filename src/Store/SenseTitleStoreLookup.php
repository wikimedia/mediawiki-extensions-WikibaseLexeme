<?php

namespace Wikibase\Lexeme\Store;

use Title;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DummyObjects\NullSenseId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class SenseTitleStoreLookup implements EntityTitleStoreLookup {

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
	 * @param SenseId $senseId
	 *
	 * @throws UnexpectedValueException
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $senseId ) {
		Assert::parameterType( SenseId::class, $senseId, '$senseId' );

		if ( $senseId instanceof NullSenseId ) {
			return null;
		}

		$title = $this->lookup->getTitleForId( $senseId->getLexemeId() );

		if ( $title === null ) {
			return null;
		}

		$title->setFragment( '#' . $senseId->getIdSuffix() );

		return $title;
	}

}
