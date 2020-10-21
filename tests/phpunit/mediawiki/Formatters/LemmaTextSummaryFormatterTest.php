<?php

declare( strict_types=1 );
namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Presentation\Content\LemmaTextSummaryFormatter;

/**
 * @covers \Wikibase\Lexeme\Presentation\Content\LemmaTextSummaryFormatter
 *
 * @license GPL-2.0-or-later
 */
class LemmaTextSummaryFormatterTest extends TestCase {

	/**
	 * @dataProvider termsProvider
	 */
	public function testSummaryFormatter( $terms, $maxLength, $expected ) {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$formatter = new LemmaTextSummaryFormatter( $language );
		$termList = new TermList( $terms );
		$output = $formatter->getSummary( $termList, $maxLength );
		$this->assertSame( $expected, $output );
	}

	public function termsProvider() {
		return [
			'basic usage' => [
				[
					new Term( 'en', 'EnglishLabel' ),
					new Term( 'de', 'ZeGermanLabel' ),
					new Term( 'fr', 'LeFrenchLabel' )
				],
				250,
				'EnglishLabel, ZeGermanLabel, LeFrenchLabel'
			],
			'cuts off text' => [
				[
					new Term( 'en', 'EnglishLabel' ),
				],
				10,
				'English...'
			],
			'empty' => [
				[],
				250,
				''
			]
		];
	}
}
