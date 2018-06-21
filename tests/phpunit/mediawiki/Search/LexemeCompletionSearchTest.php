<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use CirrusSearch\CirrusDebugOptions;
use Language;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lexeme\Search\FormSearchEntity;
use Wikibase\Lexeme\Search\LexemeSearchEntity;

/**
 * @covers \Wikibase\Lexeme\Search\LexemeSearchEntity
 */
class LexemeCompletionSearchTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}
	}

	/**
	 * @param Language $userLang
	 * @return LexemeSearchEntity
	 */
	private function newEntitySearch( Language $userLang ) {
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		return new LexemeSearchEntity(
			new BasicEntityIdParser(),
			new \FauxRequest(),
			$userLang,
			$repo->getLanguageFallbackChainFactory(),
			$repo->getPrefetchingTermLookup(),
			CirrusDebugOptions::forDumpingQueriesInUnitTests()
		);
	}

	/**
	 * @param Language $userLang
	 * @return LexemeSearchEntity
	 */
	private function newFormSearch( Language $userLang ) {
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		return new FormSearchEntity(
			new BasicEntityIdParser(),
			new \FauxRequest(),
			$userLang,
			$repo->getLanguageFallbackChainFactory(),
			$repo->getPrefetchingTermLookup(),
			CirrusDebugOptions::forDumpingQueriesInUnitTests()
		);
	}

	public function searchDataProvider() {
		return [
			"simple" => [
				'Duck',
				'simple'
			],
			"byid" => [
				'(L2)',
				'byid'
			],

		];
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param string $term search term
	 * @param string $expected Expected result filename
	 */
	public function testSearchElastic( $term, $expected ) {
		$search = $this->newEntitySearch( Language::factory( 'en' ) );
		$elasticQuery = $search->getRankedSearchResults(
			$term, 'test' /* not used so far */,
			'lexeme', 10, false
		);
		$decodedQuery = json_decode( $elasticQuery, true );
		unset( $decodedQuery['path'] );
		$encodedData = json_encode( $decodedQuery, JSON_PRETTY_PRINT );
		$this->assertFileContains(
			__DIR__ . "/../../data/lexemeCompletionSearch/$expected.expected",
			$encodedData );
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param string $term search term
	 * @param string $expected Expected result filename
	 */
	public function testSearchFormElastic( $term, $expected ) {
		$search = $this->newFormSearch( Language::factory( 'en' ) );
		$elasticQuery = $search->getRankedSearchResults(
			$term, 'test' /* not used so far */,
			'form', 10, false
		);
		$decodedQuery = json_decode( $elasticQuery, true );
		unset( $decodedQuery['path'] );
		$encodedData = json_encode( $decodedQuery, JSON_PRETTY_PRINT );
		$this->assertFileContains(
			__DIR__ . "/../../data/lexemeCompletionSearch/$expected.form.expected",
			$encodedData );
	}

}
