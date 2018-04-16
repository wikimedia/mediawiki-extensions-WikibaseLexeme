<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use DataValues\StringValue;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\Search\LexemeLanguageField;

/**
 * @covers \Wikibase\Lexeme\Search\LexemeLanguageField
 */
class LexemeLanguageFieldTest extends LexemeFieldTest {

	use PHPUnit4And6Compat;

	/**
	 * @return StatementList
	 */
	private function getStatList( PropertyId $propId, $code ) {
		$statList = new StatementList();
		$statList->addStatement( new Statement( new PropertyValueSnak( $propId,
			new StringValue( $code ) ) ) );
		return $statList;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup( StatementList $statList ) {
		$langEntity = $this->getMock( Item::class );
		$langEntity->method( 'getStatements' )->willReturn( $statList );

		$lookup = $this->getMock( EntityLookup::class );
		$lookup->method( 'getEntity' )
			->with( new ItemId( self::LANGUAGE_ID ) )
			->willReturn( $langEntity );
		return $lookup;
	}

	/**
	 * @return array
	 */
	public function getTestData() {

		$propId = new PropertyId( 'P42' );

		return [
			'no property id' => [
				new LexemeLanguageField( $this->getMock( EntityLookup::class ), null ),
				[
					'entity' => self::LANGUAGE_ID,
					'code' => null
				]
			],
			'no entity' => [
				new LexemeLanguageField( $this->getMock( EntityLookup::class ), $propId ),
				[
					'entity' => self::LANGUAGE_ID,
					'code' => null
				]
			],
			'with property id' => [
				new LexemeLanguageField(
					$this->getEntityLookup( $this->getStatList( $propId, 'fr' ) ),
					$propId ),
				[
					'entity' => self::LANGUAGE_ID,
					'code' => 'fr'
				]
			],
			'with property id, no statement' => [
				new LexemeLanguageField( $this->getEntityLookup( new StatementList() ), $propId ),
				[
					'entity' => self::LANGUAGE_ID,
					'code' => null
				]
			],
		];
	}

}
