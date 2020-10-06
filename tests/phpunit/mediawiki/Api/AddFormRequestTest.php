<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\AddFormRequest;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddFormRequest
 *
 * @license GPL-2.0-or-later
 */
class AddFormRequestTest extends TestCase {

	public function testReturnsChangeOpThatAddsForm() {
		$request = new AddFormRequest(
			new LexemeId( 'L1' ),
			new ChangeOpFormEdit( [
				new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
				new ChangeOpGrammaticalFeatures( [ new ItemId( 'Q1' ) ] )
			] ),
			1
		);

		$changeOp = $request->getChangeOp();

		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$changeOp->apply( $lexeme );

		$forms = $lexeme->getForms()->toArray();

		$this->assertCount( 1, $forms );
		$this->assertEquals( [ 'en' => 'goat' ], $forms[0]->getRepresentations()->toTextArray() );
		$this->assertEquals( [ new ItemId( 'Q1' ) ], $forms[0]->getGrammaticalFeatures() );
	}

	public function testGetLexemeId() {
		$lexemeId = new LexemeId( 'L1' );

		$request = new AddFormRequest(
			$lexemeId,
			new ChangeOpFormEdit( [
				new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
				new ChangeOpGrammaticalFeatures( [] )
			] ),
			1
		);

		$this->assertSame( $lexemeId, $request->getLexemeId() );
	}

	public function testGetBaseRevId() {
		$request = new AddFormRequest(
			new LexemeId( 'L1' ),
			new ChangeOpFormEdit( [
				new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
				new ChangeOpGrammaticalFeatures( [] )
			] ),
			1
		);

		$this->assertSame( 1, $request->getBaseRevId() );
	}

}
