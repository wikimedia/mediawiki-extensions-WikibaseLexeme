<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use Language;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
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
	 * @return \FauxRequest
	 */
	private function getMockRequest() {
		return new \FauxRequest( [ 'cirrusDumpQuery' => 'yes' ] );
	}

	/**
	 * @param Language $userLang
	 * @return LexemeSearchEntity
	 */
	private function newEntitySearch( Language $userLang ) {
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		return new LexemeSearchEntity(
			new BasicEntityIdParser(),
			$this->getMockRequest(),
			$userLang,
			$repo->getLanguageFallbackChainFactory(),
			$repo->getPrefetchingTermLookup()
		);
	}

	public function searchDataProvider() {
		return [
			"simple" => [
				'Duck',
				'simple'
			]
		];
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param string $term search term
	 * @param string $expected Expected result filename
	 */
	public function testSearchElastic( $term, $expected ) {
		$search = $this->newEntitySearch( Language::factory( 'en' ) );
		$search->setReturnResult( true );
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

}
