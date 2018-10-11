<?php
namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use CirrusSearch\Search\SearchContext;
use Elastica\Result;
use Elastica\ResultSet;
use Language;
use Wikibase\Lexeme\DataAccess\Search\LexemeTermResult;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Search\LexemeTermResult
 */
class LexemeTermResultTest extends \MediaWikiTestCase {
	use LexemeDescriptionTest;

	/**
	 * Labels for language & categories
	 * Used by LexemeDescriptionTest
	 * @var array
	 */
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
			"by id, no lang code" => [
				'en',
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L2',
						'lexeme_language' => [ 'code' => null, 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'title' => [ 'L2' ] ],
				],
				[
					'id' => 'L2',
					'label' => [ 'und', 'duck' ],
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
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L2',
						'lexeme_language' => [ 'code' => null, 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'lemma' => [ 'duck' ] ],
				],
				[
					'id' => 'L2',
					'label' => [ 'und', 'duck' ],
					'description' => [ 'en', 'English noun' ],
					'matched' => [ 'und', 'duck' ],
					'matchedType' => 'label'
				]
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
	 * @dataProvider termResultsProvider
	 */
	public function testTransformResult(
		$displayLanguage,
		array $fetchIds,
		array $resultData,
		array $expected
	) {
		$termLookupFactory = $this->getTermLookupFactory( $fetchIds, $displayLanguage );

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
