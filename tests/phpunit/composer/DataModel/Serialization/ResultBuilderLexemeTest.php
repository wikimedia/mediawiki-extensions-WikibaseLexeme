<?php

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

/**
 * Checks if ResultBuilder in Wikibase can work with Lexeme and its subentities.
 *
 * @license GPL-2.0-or-later
 */
class ResultBuilderLexemeTest extends TestCase {

	private $originalWBRepoSettings;

	protected function setUp(): void {
		global $wgWBRepoSettings;

		parent::setUp();
		$this->originalWBRepoSettings = $wgWBRepoSettings;
		$wgWBRepoSettings['tmpSerializeEmptyListsAsObjects'] = true;
		MediaWikiServices::getInstance()->resetServiceForTesting( 'WikibaseRepo.Settings' );
	}

	protected function tearDown(): void {
		global $wgWBRepoSettings;

		$wgWBRepoSettings = $this->originalWBRepoSettings;
		MediaWikiServices::getInstance()->resetServiceForTesting( 'WikibaseRepo.Settings' );
	}

	/**
	 * Removes all metadata keys as recognised by the MW Api.
	 * These all start with a '_' character.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	private function removeMetaData( array $array ) {
		foreach ( $array as $key => &$value ) {
			if ( is_string( $key ) && $key[0] === '_' ) {
				unset( $array[$key] );
			} else {
				if ( is_array( $value ) ) {
					$value = $this->removeMetaData( $value );
				}
			}
		}
		return $array;
	}

	private function getResultBuilderLexeme( ApiResult $result, bool $addMetaData = false ) {
		$mockTitle = $this->createMock( Title::class );
		$mockTitle->method( 'getArticleID' )
			->willReturn( 123 );
		$mockTitle->method( 'getNamespace' )
			->willReturn( 406 );
		$mockTitle->method( 'getPrefixedText' )
			->willReturn( 'MockPrefixedText' );

		$entityTitleStoreLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleStoreLookup->method( 'getTitleForId' )
			->willReturn( $mockTitle );

		$mockPropertyDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$mockPropertyDataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( static function ( PropertyId $id ) {
				return 'DtIdFor_' . $id->getSerialization();
			} );
		$propertyIdParser = $this->createStub( EntityIdParser::class );
		$propertyIdParser->method( 'parse' )
			->willReturnCallback( static function ( string $id ) {
				return new NumericPropertyId( $id );
			} );

		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		return new ResultBuilder(
			$result,
			$entityTitleStoreLookup,
			$serializerFactory,
			new StorageLexemeSerializer(
				$serializerFactory->newTermListSerializer(),
				$serializerFactory->newStatementListSerializer()
			),
			new HashSiteStore(),
			$mockPropertyDataTypeLookup,
			$propertyIdParser,
			$addMetaData
		);
	}

	public function provideTestAddLexemeRevision() {
		$expected = [
			'entities' => [
				'L1' => [
					'pageid' => 123, // mocked
					'ns' => 406, // mocked
					'title' => 'MockPrefixedText', // mocked
					'id' => 'L1',
					'type' => 'lexeme',
					'lastrevid' => 33,
					'modified' => '2020-11-26T20:29:23Z',
					'claims' => [
						'_element' => 'property',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					],
					'nextFormId' => 2,
					'nextSenseId' => 1,
					'senses' => [],
					'forms' => [
						[
							'representations' => [],
							'grammaticalFeatures' => [],
							'id' => 'L1-F1',
							'claims' => [
								'P65' => [
									[
										'id' => 'imaguid',
										'mainsnak' => [
											'snaktype' => 'value',
											'property' => 'P65',
											'datavalue' => [
												'value' => 'snakStringValue',
												'type' => 'string',
											],
											'datatype' => 'DtIdFor_P65',
										],
										'type' => 'statement',
										'qualifiers' => [
											'P65' => [
												[
													'hash' => '3ea0f5404dd4e631780b3386d17a15a583e499a6',
													'snaktype' => 'value',
													'property' => 'P65',
													'datavalue' => [
														'value' => 'string!',
														'type' => 'string',
													],
													'datatype' => 'DtIdFor_P65',
												],
												[
													'hash' => 'aa9a5f05e20d7fa5cda7d98371e44c0bdd5de35e',
													'snaktype' => 'somevalue',
													'property' => 'P65',
													'datatype' => 'DtIdFor_P65',
												],
												'_element' => 'qualifiers',
											],
											'_type' => 'kvp',
											'_kvpkeyname' => 'id',
											'_element' => 'property',
										],
										'rank' => 'normal',
										'qualifiers-order' => [
											'P65',
											'_element' => 'property',
										],
										'references' => [
											[
												'hash' => '8445204eb74e636cb53687e2f947c268d5186075',
												'snaks' => [
													'P65' => [
														[
															'snaktype' => 'somevalue',
															'property' => 'P65',
															'datatype' => 'DtIdFor_P65',
														],
														'_element' => 'snak',
													],
													'P68' => [
														[
															'snaktype' => 'somevalue',
															'property' => 'P68',
															'datatype' => 'DtIdFor_P68',
														],
														'_element' => 'snak',
													],
													'_type' => 'kvp',
													'_kvpkeyname' => 'id',
													'_element' => 'property',
												],
												'snaks-order' => [
													'P65',
													'P68',
													'_element' => 'property',
												]
											],
											'_element' => 'reference',
										],
									],
									'_element' => 'claim',
								],
								'_type' => 'kvp',
								'_kvpkeyname' => 'id',
								'_element' => 'property',
							],
						]
					],
				],
				'_element' => 'entity',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
				'_kvpmerge' => true,
			],
			'_type' => 'assoc',
		];

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return [
			[ false, $expectedNoMetaData ],
			[ true, $expected ],
		];
	}

	private function getDefaultResult() {
		return new ApiResult( false );
	}

	/**
	 * @dataProvider provideTestAddLexemeRevision
	 * @covers \Wikibase\Repo\Api\ResultBuilder
	 */
	public function testAddLexemeRevision( bool $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$blankForm = new BlankForm();

		$snak = new PropertyValueSnak(
			new NumericPropertyId( 'P65' ), new StringValue( 'snakStringValue' )
		);

		$qualifiers = new SnakList();
		$qualifiers->addSnak(
			new PropertyValueSnak( new NumericPropertyId( 'P65' ), new StringValue( 'string!' ) )
		);
		$qualifiers->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P65' ) ) );

		$references = new ReferenceList();
		$referenceSnaks = new SnakList();
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P65' ) ) );
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P68' ) ) );
		$references->addReference( new Reference( $referenceSnaks ) );

		$guid = 'imaguid';
		$blankForm->getStatements()->addNewStatement( $snak, $qualifiers, $references, $guid );
		$lexeme->addOrUpdateForm( $blankForm );

		$entityRevision = new EntityRevision( $lexeme, 33, '20201126202923' );

		$resultBuilder = $this->getResultBuilderLexeme( $result, $addMetaData );
		$resultBuilder->addEntityRevision( 'L1', $entityRevision );

		$data = $result->getResultData();
		unset(
			$data['entities']['L1']['lemmas'],
			$data['entities']['L1']['language'],
			$data['entities']['L1']['lexicalCategory']
		);

		$this->assertEquals( $expected, $data );
	}
}
