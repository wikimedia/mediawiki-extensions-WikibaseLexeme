<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Content\LexemeContent;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Content\LexemeContent
 *
 * @license GPL-2.0-or-later
 */
class LexemeContentTest extends TestCase {

	public function testInvalidEntityType() {
		$this->setExpectedException( InvalidArgumentException::class );
		new LexemeContent( new EntityInstanceHolder( new Item() ) );
		$this->assertTrue( true ); // Don't mark as risky
	}

	public function testGetEntity() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );

		$this->assertSame( $lexeme, $lexemeContent->getEntity() );
	}

	/**
	 * @dataProvider countableLexemeProvider
	 */
	public function testIsCountable( $lexeme ) {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertTrue( $lexemeContent->isCountable() );
	}

	public function countableLexemeProvider() {
		$countable = [];

		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$countable[] = [ $lexeme ];

		return $countable;
	}

	public function testNotCountable() {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder(
			new Lexeme( new LexemeId( 'L1' ) )
		) );
		$this->assertFalse( $lexemeContent->isCountable() );
	}

	public function testIsValid() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'test' ) ] ),
			new ItemId( 'Q120' ),
			new ItemId( 'Q121' )
		);

		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertTrue( $lexemeContent->isValid() );
	}

	/**
	 * @dataProvider provideInvalidLexeme
	 */
	public function testNotValid( $lexeme ) {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertFalse( $lexemeContent->isValid() );
	}

	public function provideInvalidLexeme() {
		yield [ new Lexeme( new LexemeId( 'L1' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, new ItemId( 'Q120' ), new ItemId( 'Q121' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, null, new ItemId( 'Q121' ) ) ];
		yield [ new Lexeme( new LexemeId( 'L1' ), null, new ItemId( 'Q120' ) ) ];
	}

}
