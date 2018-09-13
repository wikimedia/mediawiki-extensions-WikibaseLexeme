<?php

namespace Wikibase\Lexeme\Tests\DummyObjects;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DummyObjects\BlankForm;
use Wikibase\Lexeme\DummyObjects\DummyFormId;
use Wikibase\Lexeme\DummyObjects\NullFormId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\DummyObjects\BlankForm
 *
 * @license GPL-2.0-or-later
 */
class BlankFormTest extends TestCase {

	public function testGetIdWithoutConnectedLexeme_yieldsNullFormId() {
		$blankform = new BlankForm();
		$this->assertInstanceOf( NullFormId::class, $blankform->getId() );
	}

	public function testGetIdWithConnectedLexeme_yieldsDummyFormId() {
		$lexemeId = new LexemeId( 'L7' );
		$blankform = new BlankForm();
		$blankform->setLexeme( NewLexeme::havingId( $lexemeId )->build() );

		$id = $blankform->getId();
		$this->assertInstanceOf( DummyFormId::class, $id );
		$this->assertSame( $lexemeId, $id->getLexemeId() );
	}

	public function testGetIdAfterGetRealForm_yieldsRealFormId() {
		$blankform = new BlankForm();
		$blankform->setRepresentations( new TermList( [ new Term( 'de', 'Fuchs' ) ] ) );
		$formId = new FormId( 'L1-F4' );

		$blankform->getRealForm( $formId );

		$this->assertSame( $formId, $blankform->getId() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterAssertionException
	 * @expectedExceptionMessage Form must have at least one representation
	 */
	public function testGetRealFormOnIncompleteData_throwsFormConstructionExceptions() {
		$blankform = new BlankForm();
		$blankform->getRealForm( new FormId( 'L1-F4' ) );
	}

	public function testGetRealFormOnMinimalData_yieldsFormWithData() {
		$formId = new FormId( 'L1-F4' );
		$representations = new TermList( [ new Term( 'de', 'Fuchs' ) ] );
		$grammaticalFeatures = [ new ItemId( 'Q43' ) ];

		$blankform = new BlankForm();
		$blankform->setRepresentations( $representations );
		$blankform->setGrammaticalFeatures( $grammaticalFeatures );

		$form = $blankform->getRealForm( $formId );

		$this->assertInstanceOf( Form::class, $form );
		$this->assertSame( $representations, $form->getRepresentations() );
		$this->assertSame( $grammaticalFeatures, $form->getGrammaticalFeatures() );
		$this->assertEquals( new StatementList(), $form->getStatements() );
	}

}
