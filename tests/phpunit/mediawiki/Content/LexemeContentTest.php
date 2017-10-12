<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lexeme\Content\LexemeContent;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Content\LexemeContent
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexemeContentTest extends PHPUnit_Framework_TestCase {

	public function testInvalidEntityType() {
		$this->setExpectedException( InvalidArgumentException::class );
		new LexemeContent( new EntityInstanceHolder( new Item() ) );
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

	/**
	 * @dataProvider provideValidLexeme
	 */
	public function testIsValid( $lexeme ) {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertTrue( $lexemeContent->isValid() );
	}

	public function provideValidLexeme() {
		$valid = [];

		$lexeme = new Lexeme( new LexemeId( 'L1' ), null, new ItemId( 'Q120' ), new ItemId( 'Q121' ) );
		$valid[] = [ $lexeme ];

		return $valid;
	}

	/**
	 * @dataProvider provideInvalidLexeme
	 */
	public function testNotValid( $lexeme ) {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( $lexeme ) );
		$this->assertFalse( $lexemeContent->isValid() );
	}

	public function provideInvalidLexeme() {
		$invalid = [];

		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$invalid[] = [ $lexeme ];

		$lexeme = new Lexeme( new LexemeId( 'L1' ), null, null, new ItemId( 'Q121' ) );
		$invalid[] = [ $lexeme ];

		$lexeme = new Lexeme( new LexemeId( 'L1' ), null, new ItemId( 'Q120' ) );
		$invalid[] = [ $lexeme ];

		return $invalid;
	}

}
