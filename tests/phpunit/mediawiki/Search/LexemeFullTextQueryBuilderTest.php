<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use CirrusSearch\Profile\SearchProfileService;
use CirrusSearch\Query\FullTextQueryStringQueryBuilder;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\DataAccess\Search\LexemeFullTextQueryBuilder;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Search\LexemeFullTextQueryBuilder
 */
class LexemeFullTextQueryBuilderTest extends MediaWikiTestCase {
	use LexemeDescriptionTest;
	/**
	 * @var array search settings for the test
	 */
	private static $ENTITY_SEARCH_CONFIG = [
		'defaultFulltextRescoreProfile' => 'lexeme_fulltext',
	];

	public function setUp() {
		parent::setUp();
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}
	}

	public function searchDataProvider() {
		return [
			"work" => [
				"duck",
				__DIR__ . '/../../data/lexemeFulltextSearch/simple.expected'
					],
			"id" => [
				' L2-F1 ',
				__DIR__ . '/../../data/lexemeFulltextSearch/id.expected'
			]
		];
	}

	private function getConfigSettings() {
		return [
			'any'          => 0.1,
			'exact'        => 2,
			'folded'       => 1.5,
			'partial'      => 1,
			'form-discount' => 1,
		];
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param string $searchString
	 * @param string $expected
	 */
	public function testSearchElastic( $searchString, $expected ) {
		$this->setMwGlobals( [
			'wgCirrusSearchQueryStringMaxDeterminizedStates' => 500,
			'wgCirrusSearchElasticQuirks' => [],
			'wgLexemeFulltextRescoreProfile' => 'lexeme_fulltext',
		] );

		$config = new SearchConfig();

		$builder = new LexemeFullTextQueryBuilder(
			$this->getConfigSettings(),
			$this->getTermLookupFactory( [], 'en' ),
			new ItemIdParser(),
			Language::factory( 'en' )
		);

		$builderSettings = $config->getProfileService()
			->loadProfileByName( SearchProfileService::FT_QUERY_BUILDER, 'default' );
		$defaultBuilder = new FullTextQueryStringQueryBuilder( $config, [],
			$builderSettings['settings'] );
		// 146 is Lexeme namespaces
		$context = new SearchContext( $config, [ 146 ] );
		$defaultBuilder->build( $context, $searchString );
		// Dispatcher does this cleanup, so do it here
		$context->setHighlightQuery( null );
		// do the job
		$builder->build( $context, $searchString );
		$query = $context->getQuery();
		$rescore = $context->getRescore();

		// T206100
		$serializePrecision = ini_get( 'serialize_precision' );
		ini_set( 'serialize_precision', -1 );
		$encoded = json_encode( [
				'query' => $query->toArray(),
				'rescore_query' => $rescore,
				'highlight' => $context->getHighlight( $context->getResultsType() )
			],
			JSON_PRETTY_PRINT );
		ini_set( 'serialize_precision', $serializePrecision );

		$this->assertFileContains( $expected, $encoded );
	}

}
