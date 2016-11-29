<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use InvalidArgumentException;

/**
 * @covers Wikibase\Lexeme\DataModel\Lexeme
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexemeTest extends PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$id = new LexemeId( 'L1' );
		$statements = new StatementList();
		$lemma = new Term( 'fa', 'Karaj' );
		$lemmata = new TermList( [ $lemma ] );
		$lexeme = new Lexeme( $id, $lemmata, $statements );

		$this->assertSame( $id, $lexeme->getId() );
		$this->assertSame( $statements, $lexeme->getStatements() );
		$this->assertSame( $lemmata, $lexeme->getLemmata() );
	}

	public function testEmptyConstructor() {
		$lexeme = new Lexeme();

		$this->assertNull( $lexeme->getId() );
		$this->assertEquals( new StatementList(), $lexeme->getStatements() );
		$this->assertNull( $lexeme->getLemmata() );
	}

	public function testGetEntityType() {
		$this->assertSame( 'lexeme', ( new Lexeme() )->getType() );
	}

	public function testSetNewId() {
		$lexeme = new Lexeme();
		$id = new LexemeId( 'L1' );
		$lexeme->setId( $id );

		$this->assertSame( $id, $lexeme->getId() );
	}

	public function testSetNewIdAsInt() {
		$lexeme = new Lexeme();
		$lexeme->setId( 1 );

		$this->assertTrue( $lexeme->getId()->equals( new LexemeId( 'L1' ) ) );
	}

	public function testOverrideId() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$id = new LexemeId( 'L2' );
		$lexeme->setId( $id );

		$this->assertSame( $id, $lexeme->getId() );
	}

	public function provideInvalidIds() {
		return [
			[ null ],
			[ false ],
			[ 1.0 ],
			[ 'L1' ],
			[ new ItemId( 'Q1' ) ],
		];
	}

	/**
	 * @dataProvider provideInvalidIds
	 */
	public function testSetInvalidId( $id ) {
		$lexeme = new Lexeme();

		$this->setExpectedException( InvalidArgumentException::class );
		$lexeme->setId( $id );
	}

	public function testIsEmpty() {
		$this->assertTrue( ( new Lexeme() )->isEmpty() );
		$this->assertTrue( ( new Lexeme( new LexemeId( 'L1' ) ) )->isEmpty() );
	}

	public function testIsNotEmptyWithStatement() {
		$lexeme = new Lexeme();
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertFalse( $lexeme->isEmpty() );
	}

	public function testIsNotEmptyWithLemmata() {
		$lemmata = new TermList( [ new Term( 'zh', 'Beijing' ) ] );
		$lexeme = new Lexeme( new LexemeId( 'l1' ), $lemmata );

		$this->assertFalse( $lexeme->isEmpty() );
	}

	public function testIsNotEmptyWithLemmataAndStatement() {
		$lemmata = new TermList( [ new Term( 'zh', 'Beijing' ) ] );
		$lexeme = new Lexeme( new LexemeId( 'l1' ), $lemmata );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertFalse( $lexeme->isEmpty() );
	}

	public function equalLexemesProvider() {
		$empty = new Lexeme();

		$withStatement = new Lexeme();
		$withStatement->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		return [
			'empty' => [
				$empty,
				new Lexeme()
			],
			'same id' => [
				new Lexeme( new LexemeId( 'L1' ) ),
				new Lexeme( new LexemeId( 'L1' ) )
			],
			'different id' => [
				new Lexeme( new LexemeId( 'L1' ) ),
				new Lexeme( new LexemeId( 'L2' ) )
			],
			'no id' => [
				new Lexeme( new LexemeId( 'L1' ) ),
				$empty
			],
			'same object' => [
				$empty,
				$empty
			],
			'same statements' => [
				$withStatement,
				clone $withStatement
			],
		];
	}

	/**
	 * @dataProvider equalLexemesProvider
	 */
	public function testEquals( Lexeme $a, Lexeme $b ) {
		$this->assertTrue( $a->equals( $b ) );
	}

	public function testEqualLemmata() {
		$lexeme = new Lexeme();
		$lemmata = new TermList( [ new Term( 'es', 'Barcelona' ) ] );
		$lexeme->setLemmata( $lemmata );
		$this->assertFalse( $lexeme->getLemmata()->equals( null ) );
	}

	public function differentLexemesProvider() {
		$withStatement1 = new Lexeme();
		$withStatement1->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$withStatement2 = new Lexeme();
		$withStatement2->getStatements()->addNewStatement( new PropertyNoValueSnak( 24 ) );

		$lemmata1 = new TermList( [ new Term( 'fa', 'Shiraz' ) ] );
		$lemmata2 = new TermList( [ new Term( 'fa', 'Tehran' ) ] );
		return [
			'null' => [
				new Lexeme(),
				null
			],
			'item' => [
				new Lexeme(),
				new Item()
			],
			'different statements' => [
				$withStatement1,
				$withStatement2
			],
		    'different lemmata' => [
				new Lexeme( new LexemeId( 'l1' ), $lemmata1 ),
				new Lexeme( new LexemeId( 'l1' ), $lemmata2 ),
		    ]
		];
	}

	/**
	 * @dataProvider differentLexemesProvider
	 */
	public function testNotEquals( Lexeme $a, $b ) {
		$this->assertFalse( $a->equals( $b ) );
	}

	public function testCopyEmptyEquals() {
		$lexeme = new Lexeme();

		$this->assertEquals( $lexeme, $lexeme->copy() );
	}

	public function testCopyWithIdEquals() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );

		$this->assertEquals( $lexeme, $lexeme->copy() );
	}

	public function testCopyWithContentEquals() {
		$lemmata = new TermList( [ new Term( 'de', 'Cologne' ) ] );
		$lexeme = new Lexeme( new LexemeId( 'L1' ), $lemmata );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertEquals( $lexeme, $lexeme->copy() );
	}

	public function testCopyObjectReferences() {
		$id = new LexemeId( 'L1' );
		$statements = new StatementList();

		$lexeme = new Lexeme( $id, null, $statements );
		$copy = $lexeme->copy();

		$this->assertSame( $id, $copy->getId() );
		$this->assertNotSame( $statements, $copy->getStatements() );
	}

	public function testCopyModification() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$copy = $lexeme->copy();

		$copy->setId( new LexemeId( 'L2' ) );
		$copy->getStatements()->addNewStatement( new PropertyNoValueSnak( 24 ) );
		$copy->getStatements()->getFirstStatementWithGuid( null )->setRank(
			Statement::RANK_DEPRECATED
		);

		$this->assertSame( 'L1', $lexeme->getId()->getSerialization() );
		$this->assertCount( 1, $lexeme->getStatements() );
		$this->assertSame(
			Statement::RANK_NORMAL,
			$lexeme->getStatements()->getFirstStatementWithGuid( null )->getRank()
		);
	}

	public function testGetFingerprintSetFingerprint() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );

		$fingerprint = new Fingerprint( new TermList( [ new Term( 'en', 'English label' ) ] ) );

		$this->assertTrue( $lexeme->getFingerprint()->isEmpty() );

		$lexeme->setFingerprint( $fingerprint );

		$this->assertSame( $fingerprint, $lexeme->getFingerprint() );
	}

	public function testSetLemmata() {
		$id = new LexemeId( 'L1' );
		$lemmata = new TermList( [ new Term( 'fa', 'Karaj' ) ] );

		$lexeme = new Lexeme( $id );
		$lexeme->setLemmata( $lemmata );

		$this->assertSame( $lemmata, $lexeme->getLemmata() );
	}

}
