<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lexeme\Content\LexemeContent;
use Wikibase\Lexeme\DataModel\Lexeme;

/**
 * @covers Wikibase\Lexeme\Content\LexemeContent
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
		$lexeme = new Lexeme();
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

		$lexeme = new Lexeme();
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$countable[] = [ $lexeme ];

		return $countable;
	}

	public function testNotCountable() {
		$lexemeContent = new LexemeContent( new EntityInstanceHolder( new Lexeme() ) );
		$this->assertFalse( $lexemeContent->isCountable() );
	}

}
