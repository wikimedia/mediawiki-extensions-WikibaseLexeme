<?php
namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use CirrusSearch\Search\SearchContext;
use Elastica\Response;
use Elastica\Result;
use Elastica\ResultSet;
use Language;
use MediaWikiTestCase;
use Wikibase\Lexeme\DataAccess\Search\LexemeFulltextResult;
use Wikibase\Lexeme\DataAccess\Search\LexemeResult;
use Wikibase\Lexeme\DataAccess\Search\LexemeResultSet;
use Wikibase\Lexeme\Tests\MediaWiki\LexemeDescriptionTestCase;

// phpcs:disable Generic.Files.LineLength.TooLong
// phpcs:disable Generic.Files.LineLength.MaxExceeded
// We need long template strings here...
/**
 * @covers \Wikibase\Lexeme\DataAccess\Search\LexemeFulltextResult
 * @covers \Wikibase\Lexeme\DataAccess\Search\LexemeResult
 * @covers \Wikibase\Lexeme\DataAccess\Search\LexemeResultSet
 */
class LexemeFulltextResultTest extends MediaWikiTestCase {
	use LexemeDescriptionTestCase;

	/**
	 * Labels for language & categories
	 * Used by LexemeDescriptionTest
	 * @var array
	 */
	private $labels = [
		'Q1' => [
			'en' => 'English',
			'de' => 'Englische',
			'qqx' => 'Anglais',
		],
		'Q2' => [
			'en' => 'noun',
			'de' => 'Substantiv',
			'qqx' => 'nom',
		],
		'Q3' => [
			'en' => 'singular',
			'qqx' => 'singulier'
		],
		'Q4' => [
			'en' => 'plural',
		],
		'Q5' => [
			'en' => 'nominative',
		],
		'Q6' => [
			'ru' => 'настоящее время'
		]
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
					'lemma' => 'duck',
					'lang' => 'Q1',
					'langcode' => 'en',
					'category' => 'Q2',
					'title' => 'duck',
					'description' => '<span class="wb-itemlink-description">English, noun</span>'
				]
			],
			"by lemma de" => [
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
					'lemma' => 'duck',
					'lang' => 'Q1',
					'langcode' => 'en',
					'category' => 'Q2',
					'title' => 'duck',
					'description' => '<span class="wb-itemlink-description">Englische, Substantiv</span>'
				]
			],
			'by id' => [
				'en',
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
					],
					'highlight' => [ 'title' => [ 'L1' ] ],
				],
				[
					'id' => 'L1',
					'lemma' => 'duck',
					'lang' => 'Q1',
					'langcode' => 'en',
					'category' => 'Q2',
					'title' => 'duck',
					'description' => '<span class="wb-itemlink-description">English, noun</span>'
				]

			],
			'by form id' => [
				'qqx',
				[ 'Q1', 'Q2', 'Q3' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[ 'id' => 'L1-F1', 'representation' => [ 'ducks', 'geese' ] ],
							[
								'id' => 'L1-F2',
								'representation' => [ 'moreducks', 'moregeese' ],
								'features' => [ 'Q3' ],
							],
						],
					],
					'highlight' => [ 'lexeme_forms.id' => [ 'L1-F2' ] ],
				],
				[
					'id' => 'L1',
					'lemma' => 'duck',
					'lang' => 'Q1',
					'langcode' => 'en',
					'category' => 'Q2',
					'formId' => 'L1-F2',
					'representation' => 'moreducks',
					'features' => [ 'Q3' ],
					'title' => 'moreducks',
					'description' =>
						'<span class="wb-itemlink-description">(wikibaselexeme-form-description: singulier, duck, L1, (wikibaselexeme-description: Anglais, nom))</span>'
				]
			],
			'by form repr' => [
				'qqx',
				[ 'Q1', 'Q2', 'Q4' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'id' => 'L1-F1',
								'representation' => [ 'ducks', 'geese' ],
								'features' => [ 'Q4' ],
							],
							[
								'id' => 'L1-F2',
								'representation' => [ 'moreducks', 'moregeese' ],
								'features' => [ 'Q3' ],
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'ducks' ] ],
				],
				[
					'id' => 'L1',
					'lemma' => 'duck',
					'lang' => 'Q1',
					'langcode' => 'en',
					'category' => 'Q2',
					'formId' => 'L1-F1',
					'representation' => 'ducks',
					'features' => [ 'Q4' ],
					'title' => 'ducks',
					'description' =>
						'<span class="wb-itemlink-description">(wikibaselexeme-form-description: plural, duck, L1, (wikibaselexeme-description: Anglais, nom))</span>'
				]
			],
			'by another form repr' => [
				'qqx',
				[ 'Q1', 'Q2', 'Q3' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'id' => 'L1-F1',
								'representation' => [ 'ducks', 'geese' ],
								'features' => [ 'Q4' ],
							],
							[
								'id' => 'L1-F2',
								'representation' => [ 'moreducks', 'moregeese' ],
								'features' => [ 'Q3' ],
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'moregeese' ] ],
				],
				[
					'id' => 'L1',
					'lemma' => 'duck',
					'lang' => 'Q1',
					'langcode' => 'en',
					'category' => 'Q2',
					'formId' => 'L1-F2',
					'representation' => 'moregeese',
					'features' => [ 'Q3' ],
					'title' => 'moregeese',
					'description' =>
						'<span class="wb-itemlink-description">(wikibaselexeme-form-description: singulier, duck, L1, (wikibaselexeme-description: Anglais, nom))</span>'
				]
			],
			'empty results' => [
				'qqx',
				[],
				null,
				[]
			],
		];
	}

	/**
	 * @dataProvider termResultsProvider
	 */
	public function testTransformResult(
		$displayLanguage,
		array $fetchIds,
		$resultData,
		array $expected
	) {
		$termLookupFactory = $this->getTermLookupFactory( $fetchIds, $displayLanguage );

		$res = new LexemeFulltextResult(
			$this->getIdParser(),
			Language::factory( $displayLanguage ),
			$termLookupFactory
		);

		$context = $this->getMockBuilder( SearchContext::class )
			->disableOriginalConstructor()->getMock();

		$resultSet = $this->getMockBuilder( ResultSet::class )
			->disableOriginalConstructor()->getMock();
		if ( is_null( $resultData ) ) {
			$resultSet->expects( $this->any() )->method( 'getResults' )->willReturn( [] );
		} else {
			$result = new Result( $resultData );
			$resultSet->expects( $this->any() )->method( 'getResults' )->willReturn( [ $result ] );
		}
		$resultSet->expects( $this->any() )
			->method( 'getResponse' )
			->willReturn( new Response( '{}', 200 ) );

		$converted = $res->transformElasticsearchResult( $context, $resultSet );
		if ( empty( $expected ) ) {
			$this->assertCount( 0, $converted );
			return;
		}

		/**
		 * @var LexemeResultSet $converted
		 */
		$this->assertInstanceOf( LexemeResultSet::class, $converted );
		$this->assertCount( 1, $converted );

		$rawResults = $converted->getRawResults();
		$this->assertCount( 1, $rawResults );

		$rawResult = reset( $rawResults );
		// Check raw data
		$this->assertEquals( $expected['id'], $rawResult['lexemeId']->getSerialization(),
			'Bad lexeme ID' );
		$this->assertEquals( $expected['lemma'], $rawResult['lemma'],
			'Bad lemma match' );
		$this->assertEquals( $expected['lang'], $rawResult['lang'],
			'Bad language match' );
		$this->assertEquals( $expected['langcode'], $rawResult['langcode'],
			'Bad langcode match' );
		$this->assertEquals( $expected['category'], $rawResult['category'],
			'Bad category match' );

		if ( isset( $expected['formId'] ) ) {
			$this->assertEquals( $expected['formId'], $rawResult['formId'],
				'Bad form ID match' );
			$this->assertEquals( $expected['representation'], $rawResult['representation'],
				'Bad representation match' );
			$this->assertEquals( $expected['features'], $rawResult['features'],
				'Bad features match' );
		}

		$results = $converted->extractResults();
		$this->assertCount( 1, $results );

		$result = reset( $results );
		$this->assertInstanceOf( LexemeResult::class, $result );
		/**
		 * @var LexemeResult $result
		 */
		$this->assertEquals( $expected['title'], $result->getTitleSnippet(), "Bad title" );
		$this->assertEquals( $expected['description'], $result->getTextSnippet( [] ),
			"Bad description" );
	}

}
