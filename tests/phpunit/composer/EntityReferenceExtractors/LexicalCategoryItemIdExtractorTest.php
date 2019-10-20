<?php

namespace Wikibase\Lexeme\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexicalCategoryItemIdExtractor;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexicalCategoryItemIdExtractor
 *
 * @license GPL-2.0-or-later
 */
class LexicalCategoryItemIdExtractorTest extends TestCase {

	public function testExtractEntityIds() {
		$lexCat = new ItemId( 'Q777' );
		$lexeme = NewLexeme::havingId( 'L123' )
			->withLexicalCategory( $lexCat )
			->build();
		$extractor = new LexicalCategoryItemIdExtractor();

		$this->assertEquals( [ $lexCat ], $extractor->extractEntityIds( $lexeme ) );
	}

	/**
	 * @dataProvider nonLexemeProvider
	 */
	public function testGivenNotALexeme_throwsException( $nonLexeme ) {
		$extractor = new LexicalCategoryItemIdExtractor();
		$this->expectException( InvalidArgumentException::class );
		$extractor->extractEntityIds( $nonLexeme );
	}

	public function nonLexemeProvider() {
		return [
			[ new Item() ],
			[ new Property( null, null, 'string' ) ],
		];
	}

}
