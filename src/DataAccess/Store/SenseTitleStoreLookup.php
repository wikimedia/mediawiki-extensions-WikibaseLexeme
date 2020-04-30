<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use Title;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;
use Wikibase\Lexeme\Domain\Model\SenseId;
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
