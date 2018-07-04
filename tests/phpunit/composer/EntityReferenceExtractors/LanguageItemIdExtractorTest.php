<?php

namespace Wikibase\Lexeme\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lexeme\EntityReferenceExtractors\LanguageItemIdExtractor;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\EntityReferenceExtractors\LanguageItemIdExtractor
 *
 * @license GPL-2.0-or-later
 */
class LanguageItemIdExtractorTest extends TestCase {

	public function testExtractEntityIds() {
		$language = new ItemId( 'Q777' );
		$lexeme = NewLexeme::havingId( 'L123' )
			->withLanguage( $language )
			->build();
		$extractor = new LanguageItemIdExtractor();

		$this->assertEquals( [ $language ], $extractor->extractEntityIds( $lexeme ) );
	}

	/**
	 * @dataProvider nonLexemeProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNotALexeme_throwsException( $nonLexeme ) {
		$extractor = new LanguageItemIdExtractor();
		$extractor->extractEntityIds( $nonLexeme );
	}

	public function nonLexemeProvider() {
		return [
			[ new Item() ],
			[ new Property( null, null, 'string' ) ],
		];
	}

}
