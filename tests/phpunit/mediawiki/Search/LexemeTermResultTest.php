<?php
namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use CirrusSearch\Search\SearchContext;
use Elastica\Result;
use Elastica\ResultSet;
use Language;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Search\LexemeTermResult;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @covers \Wikibase\Lexeme\Search\LexemeTermResult
 */
class LexemeTermResultTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}
	}

	private $labels = [
		'Q1' => [
			'en' => 'English',
			'de' => 'Englische',
			'fr' => 'Anglais',
		],
		'Q2' => [
			'en' => 'noun',
			'de' => 'Substantiv',
			'fr' => 'nom',
		],
		'Q3' => [
			'ru' => 'превед',
		],
	];

	private function getMockLabel( $id, $language ) {
		if ( !isset( $this->labels[$id] ) ) {
			throw new TermLookupException( $id, $language );
		}
		if ( !isset( $this->labels[$id][$language] ) ) {
			return null;
		}
		return $this->labels[$id][$language];
	}

	/**
	 * @return TermLookup
	 */
	private function getMockTermLookup() {
		$lookup = $this->getMockBuilder( TermLookup::class )->disableOriginalConstructor()->getMock();
		$lookup->method( 'getLabel' )->willReturnCallback( function ( EntityId $id, $language ) {
			return $this->getMockLabel( $id->getSerialization(), $language );
		} );
		$lookup->method( 'getLabels' )->willReturnCallback( function ( EntityId $id, array $languages ) {
			$result = [];
			foreach ( $languages as $language ) {
				$result[$language] = $this->getMockLabel( $id->getSerialization(), $language );
			}
			return $result;
		} );
		return $lookup;
	}

	/**
	 * @param $lookupIds
	 * @return TermBuffer
	 */
	private function getMockTermBuffer( $lookupIds, $languages ) {
		$fetchIds = array_combine( $lookupIds, array_map( function ( $id ) {
			return new ItemId( $id );
		}, $lookupIds ) );

		$lookup = $this->getMockBuilder( TermBuffer::class )->disableOriginalConstructor()->getMock();
		if ( empty( $lookupIds ) ) {
			$lookup->expects( $this->never() )
				->method( 'prefetchTerms' );
			return $lookup;
		}
		$lookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with( $fetchIds, [ 'label' ], $languages )
			->willReturn( true );
		return $lookup;
	}

	public function termResultsProvider() {
		return [
			"by lemma" => [
				'en',
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'lemma' => [ 'duck' ] ],
				],
				[
					'id' => 'L1',
					'label' => [ 'en', 'duck' ],
					'description' => [ 'en', 'English noun' ],
					'matched' => [ 'en', 'duck' ],
					'matchedType' => 'label'
				]
			],
			"by id" => [
				'en',
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L2',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'title' => [ 'L2' ] ],
				],
				[
					'id' => 'L2',
					'label' => [ 'en', 'duck' ],
					'description' => [ 'en', 'English noun' ],
					'matched' => [ 'qid', 'L2' ],
					'matchedType' => 'entityId'
				]
			],
			"by form" => [
				'en',
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L2',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'geese' ] ],
				],
				[
					'id' => 'L2',
					'label' => [ 'en', 'duck' ],
					'description' => [ 'en', 'English noun' ],
					'matched' => [ 'en', 'geese' ],
					'matchedType' => 'alias'
				]
			],
			"missing language code" => [
				'en',
				[],
				[
					'_source' => [
						'title' => 'L2',
						'lexeme_language' => [ 'code' => null, 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'lemma' => [ 'duck' ] ],
				],
				[]
			],
			"in German" => [
				'de',
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'lemma' => [ 'duck' ] ],
				],
				[
					'id' => 'L1',
					'label' => [ 'en', 'duck' ],
					'description' => [ 'de', 'Englische Substantiv' ],
					'matched' => [ 'en', 'duck' ],
					'matchedType' => 'label'
				]
			],
			"language fallback" => [
				'de-ch',
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'lemma' => [ 'duck' ] ],
				],
				[
					'id' => 'L1',
					'label' => [ 'en', 'duck' ],
					'description' => [ 'de-ch', 'Englische Substantiv' ],
					'matched' => [ 'en', 'duck' ],
					'matchedType' => 'label'
				]
			],
			"category without labels" => [
				'en',
				[ 'Q1', 'Q3' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q3',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'lemma' => [ 'duck' ] ],
				],
				[
					'id' => 'L1',
					'label' => [ 'en', 'duck' ],
					'description' => [ 'en', 'English Unknown' ],
					'matched' => [ 'en', 'duck' ],
					'matchedType' => 'label'
				]
			],
			"language without labels" => [
				'en',
				[ 'Q3', 'Q2' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q3' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'lemma' => [ 'duck' ] ],
				],
				[
					'id' => 'L1',
					'label' => [ 'en', 'duck' ],
					'description' => [ 'en', 'Unknown language noun' ],
					'matched' => [ 'en', 'duck' ],
					'matchedType' => 'label'
				]
			],

		];
	}

	/**
	 * @return EntityIdParser
	 */
	private function getIdParser() {
		return new DispatchingEntityIdParser(
			[
				LexemeId::PATTERN => function ( $s ) {
					return new LexemeId( $s );
				},
				ItemId::PATTERN => function ( $s ) {
					return new ItemId( $s );
				},
			]
		);
	}

	/**
	 * @dataProvider termResultsProvider
	 */
	public function testTransformResult(
		$displayLanguage,
		array $fetchIds,
		array $resultData,
		array $expected
	) {
		$langFactory = new LanguageFallbackChainFactory();

		$termLookupFactory = new LanguageFallbackLabelDescriptionLookupFactory(
			$langFactory,
			$this->getMockTermLookup(),
			$this->getMockTermBuffer( $fetchIds,
				$langFactory->newFromLanguageCode( $displayLanguage )->getFetchLanguageCodes() )
		);

		$res = new LexemeTermResult(
			$this->getIdParser(),
			Language::factory( $displayLanguage ),
			$termLookupFactory
		);

		$context = $this->getMockBuilder( SearchContext::class )
			->disableOriginalConstructor()->getMock();

		$result = new Result( $resultData );
		$resultSet = $this->getMockBuilder( ResultSet::class )
			->disableOriginalConstructor()->getMock();
		$resultSet->expects( $this->once() )->method( 'getResults' )->willReturn( [ $result ] );

		$converted = $res->transformElasticsearchResult( $context, $resultSet );
		if ( empty( $expected ) ) {
			$this->assertCount( 0, $converted );
			return;
		}
		$this->assertCount( 1, $converted );
		$this->assertArrayHasKey( $expected['id'], $converted );
		$converted = $converted[$expected['id']];

		$this->assertEquals( $expected['id'], $converted->getEntityId()->getSerialization(),
			'ID is wrong' );

		$this->assertEquals( $expected['label'][0],
			$converted->getDisplayLabel()->getLanguageCode(), 'Label language is wrong' );
		$this->assertEquals( $expected['label'][1], $converted->getDisplayLabel()->getText(),
			'Label text is wrong' );

		$this->assertEquals( $expected['matched'][0],
			$converted->getMatchedTerm()->getLanguageCode(), 'Matched language is wrong' );
		$this->assertEquals( $expected['matched'][1], $converted->getMatchedTerm()->getText(),
			'Matched text is wrong' );

		$this->assertEquals( $expected['matchedType'], $converted->getMatchedTermType(),
			'Match type is wrong' );

		if ( !empty( $expected['description'] ) ) {
			$this->assertEquals( $expected['description'][0],
				$converted->getDisplayDescription()->getLanguageCode(),
				'Description language is wrong' );
			$this->assertEquals( $expected['description'][1],
				$converted->getDisplayDescription()->getText(), 'Description text is wrong' );
		} else {
			$this->assertNull( $converted->getDisplayDescription() );
		}
	}

}
