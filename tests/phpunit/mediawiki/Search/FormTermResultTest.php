<?php
namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use CirrusSearch\Search\SearchContext;
use Elastica\Result;
use Elastica\ResultSet;
use Language;
use Wikibase\Lexeme\DataAccess\Search\FormTermResult;
use Wikibase\Lexeme\Tests\MediaWiki\LexemeDescriptionTestCase;

// phpcs:disable Generic.Files.LineLength.TooLong
// We need long template strings here...
/**
 * @covers \Wikibase\Lexeme\DataAccess\Search\FormTermResult
 */
class FormTermResultTest extends \PHPUnit_Framework_TestCase {
	use LexemeDescriptionTestCase;

	private $labels = [
		'Q1' => [
			'en' => 'English',
			'qqx' => 'Anglais',
		],
		'Q2' => [
			'en' => 'noun',
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
			"one form" => [
				'en',
				[ 'Q1', 'Q2', 'Q3' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'features' => [ 'Q3' ],
								'id' => 'L1-F1',
								'representation' => [ "duck" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'duck' ] ],
				],
				[
					'L1-F1' => [
						'id' => 'L1-F1',
						'label' => [ 'en', 'duck' ],
						'description' => [ 'en', 'singular for: duck (L1): English, noun' ],
						'matched' => [ 'en', 'duck' ],
						'matchedType' => 'label'
					],
				]
			],
			"many forms" => [
				'en',
				[ 'Q1', 'Q2', 'Q3', 'Q4' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'features' => [ 'Q3' ],
								'id' => 'L1-F1',
								'representation' => [ "duck", 'Duck' ]
							],
							[
								'features' => [ 'Q4' ],
								'id' => 'L1-F2',
								'representation' => [ 'ducks', "Ducks" ]
							],
							[
								'features' => [ 'Q4', 'Q3' ],
								'id' => 'L1-F3',
								'representation' => [ 'duck', "ducks" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'duck', 'ducks' ] ],
				],
				[
					'L1-F1' => [
						'id' => 'L1-F1',
						'label' => [ 'en', 'duck' ],
						'description' => [ 'en', 'singular for: duck (L1): English, noun' ],
						'matched' => [ 'en', 'duck' ],
						'matchedType' => 'label'
					],
					'L1-F2' => [
						'id' => 'L1-F2',
						'label' => [ 'en', 'ducks' ],
						'description' => [ 'en', 'plural for: duck (L1): English, noun' ],
						'matched' => [ 'en', 'ducks' ],
						'matchedType' => 'label'
					],
				]
			],
			// Many features
			"multiple features" => [
				'en',
				[ 'Q1', 'Q2', 'Q3', 'Q5' ],
				[
					'_source' => [
						'title' => 'L3',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'features' => [ 'Q3', 'Q5' ],
								'id' => 'L3-F1',
								'representation' => [ "duck" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'ducks', 'duck' ] ],
				],
				[
					'L3-F1' => [
						'id' => 'L3-F1',
						'label' => [ 'en', 'duck' ],
						'description' => [ 'en', 'singular, nominative for: duck (L3): English, noun' ],
						'matched' => [ 'en', 'duck' ],
						'matchedType' => 'label'
					],
				]

			],
			// By id
			"id match" => [
				'en',
				[ 'Q1', 'Q2', 'Q4' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'features' => [ 'Q3' ],
								'id' => 'L1-F1',
								'representation' => [ "duck" ]
							],
							[
								'features' => [ 'Q4' ],
								'id' => 'L1-F2',
								'representation' => [ "ducks" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.id' => [ 'L1-F2' ] ],
				],
				[
					'L1-F2' => [
						'id' => 'L1-F2',
						'label' => [ 'en', 'ducks' ],
						'description' => [ 'en', 'plural for: duck (L1): English, noun' ],
						'matched' => [ 'qid', 'L1-F2' ],
						'matchedType' => 'entityId'
					],
				]

			],
			// In different language
			"in language" => [
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
								'features' => [ 'Q3' ],
								'id' => 'L1-F1',
								'representation' => [ "duck" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'duck' ] ],
				],
				[
					'L1-F1' => [
						'id' => 'L1-F1',
						'label' => [ 'en', 'duck' ],
						// TODO: this depends on template, not sure how to fix
						'description' => [
							'qqx',
							'(wikibaselexeme-form-description: singulier, duck, L1, (wikibaselexeme-description: Anglais, nom))',
						],
						'matched' => [ 'en', 'duck' ],
						'matchedType' => 'label'
					],
				]
			],
			// No features
			"no feature" => [
				'en',
				[ 'Q1', 'Q2' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'features' => [],
								'id' => 'L1-F1',
								'representation' => [ "duck" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'duck' ] ],
				],
				[
					'L1-F1' => [
						'id' => 'L1-F1',
						'label' => [ 'en', 'duck' ],
						'description' => [ 'en', 'no features for: duck (L1): English, noun' ],
						'matched' => [ 'en', 'duck' ],
						'matchedType' => 'label'
					],
				]
			],
			// Not sure whether this can really happen in practice...
			"bad match" => [
				'en',
				[],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'features' => [],
								'id' => 'L1-F1',
								'representation' => [ "duck" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'goose' ] ],
				],
				[]
			],
			"empty match" => [
				'en',
				[],
				[],
				[]
			],
			// No label for feature in given language
			"no label - fallback" => [
				'qqx',
				[ 'Q1', 'Q5', 'Q4' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q5',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'features' => [ 'Q4' ],
								'id' => 'L1-F1',
								'representation' => [ "duck" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'duck' ] ],
				],
				[
					'L1-F1' => [
						'id' => 'L1-F1',
						'label' => [ 'en', 'duck' ],
						'description' => [
							'qqx',
							'(wikibaselexeme-form-description: plural, duck, L1, (wikibaselexeme-description: Anglais, nominative))',
						],
						'matched' => [ 'en', 'duck' ],
						'matchedType' => 'label'
					],
				]
			],
			//
			"no label" => [
				'qqx',
				[ 'Q1', 'Q2', 'Q6' ],
				[
					'_source' => [
						'title' => 'L1',
						'lexeme_language' => [ 'code' => 'en', 'entity' => 'Q1' ],
						'lexical_category' => 'Q2',
						'lemma' => [ 'duck', 'goose' ],
						'lexeme_forms' => [
							[
								'features' => [ 'Q6' ],
								'id' => 'L1-F1',
								'representation' => [ "duck" ]
							],
						],
					],
					'highlight' => [ 'lexeme_forms.representation' => [ 'duck' ] ],
				],
				[
					'L1-F1' => [
						'id' => 'L1-F1',
						'label' => [ 'en', 'duck' ],
						'description' => [
							'qqx',
							'(wikibaselexeme-form-description: (wikibaselexeme-unknown-category), duck, L1, (wikibaselexeme-description: Anglais, nom))',
						],
						'matched' => [ 'en', 'duck' ],
						'matchedType' => 'label'
					],
				]
			],

		];
	}

	/**
	 * @dataProvider termResultsProvider
	 */
	public function testTransformElasticsearchResult(
		$displayLanguage,
		array $fetchIds,
		array $resultData,
		array $expected
	) {
		$termLookupFactory = $this->getTermLookupFactory( $fetchIds, $displayLanguage );

		$res = new FormTermResult(
			$this->getIdParser(),
			Language::factory( $displayLanguage ),
			$termLookupFactory,
			2
		);

		$context = $this->getMockBuilder( SearchContext::class )
			->disableOriginalConstructor()->getMock();

		$result = new Result( $resultData );
		$resultSet = $this->getMockBuilder( ResultSet::class )
			->disableOriginalConstructor()->getMock();
		$resultSet->expects( $this->once() )->method( 'getResults' )
			->willReturn( $resultData ? [ $result ] : [] );

		$converted = $res->transformElasticsearchResult( $context, $resultSet );
		if ( empty( $expected ) ) {
			$this->assertCount( 0, $converted );
			return;
		}
		$this->assertCount( count( $expected ), $converted );
		foreach ( $converted as $idx => $item ) {
			$this->assertArrayHasKey( $idx, $expected );
			$expectedEntry = $expected[$idx];
			$this->assertEquals( $expectedEntry['id'], $item->getEntityId()->getSerialization(),
				'ID is wrong' );

			$this->assertEquals( $expectedEntry['label'][0],
				$item->getDisplayLabel()->getLanguageCode(), 'Label language is wrong' );
			$this->assertEquals( $expectedEntry['label'][1], $item->getDisplayLabel()->getText(),
				'Label text is wrong' );

			$this->assertEquals( $expectedEntry['matched'][0],
				$item->getMatchedTerm()->getLanguageCode(), 'Matched language is wrong' );
			$this->assertEquals( $expectedEntry['matched'][1], $item->getMatchedTerm()->getText(),
				'Matched text is wrong' );

			$this->assertEquals( $expectedEntry['matchedType'], $item->getMatchedTermType(),
				'Match type is wrong' );

			$this->assertEquals( $expectedEntry['description'][0],
				$item->getDisplayDescription()->getLanguageCode(),
				'Description language is wrong' );
			$this->assertEquals( $expectedEntry['description'][1],
				$item->getDisplayDescription()->getText(), 'Description text is wrong' );
		}
	}

}
