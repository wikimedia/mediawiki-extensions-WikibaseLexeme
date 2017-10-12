<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Api\AddFormRequest;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\Api\AddFormRequest
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class AddFormRequestTest extends \PHPUnit_Framework_TestCase {

	public function testReturnsChangeOpThatAddsForm() {
		$request = new AddFormRequest(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'goat' ) ] ),
			[ new ItemId( 'Q1' ) ]
		);

		$changeOp = $request->getChangeOp();

		$lexeme = NewLexeme::create()->build();

		$changeOp->apply( $lexeme );

		$forms = $lexeme->getForms()->toArray();

		$this->assertCount( 1, $forms );
		$this->assertEquals( [ 'en' => 'goat' ], $forms[0]->getRepresentations()->toTextArray() );
		$this->assertEquals( [ new ItemId( 'Q1' ) ], $forms[0]->getGrammaticalFeatures() );
	}

	public function testGivenNonItemsAsGrammaticalFeatures_constructorThrowsException() {
		$this->setExpectedException( \InvalidArgumentException::class );

		new AddFormRequest(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'goat' ) ] ),
			[ 'foo' ]
		);
	}

	public function testGivenEmptyRepresentationList_constructorThrowsException() {
		$this->setExpectedException( \InvalidArgumentException::class );

		new AddFormRequest( new LexemeId( 'L1' ), new TermList(), [] );
	}

	public function testGetLexemeId() {
		$lexemeId = new LexemeId( 'L1' );

		$request = new AddFormRequest( $lexemeId, new TermList( [ new Term( 'en', 'goat' ) ] ), [] );

		$this->assertSame( $lexemeId, $request->getLexemeId() );
	}

}
