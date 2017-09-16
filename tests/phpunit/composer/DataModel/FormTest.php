<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @covers \Wikibase\Lexeme\DataModel\Form
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class FormTest extends PHPUnit_Framework_TestCase {

	public function testCreateFormWithoutRepresentations_ThrowsAnException() {
		$this->setExpectedException( InvalidArgumentException::class );
		new Form( new FormId( 'F1' ), new TermList(), [] );
	}

	public function testCreateFormWithOneRepresentation_CreatesIt() {
		new Form(
			new FormId( 'F1' ),
			new TermList( [ new Term( 'en', 'representation' ) ] ),
			[]
		);
	}

	public function testCreateForm_GrammaticalFeaturesIsNotAnArrayOfItemIds_ThrowsAnException() {
		$this->setExpectedException( InvalidArgumentException::class );
		new Form(
			new FormId( 'F1' ),
			new TermList( [ new Term( 'en', 'representation' ) ] ),
			[ 1 ]
		);
	}

	public function testSetGrammaticalFeatures_RemovesDuplicateItemIds() {
		$form = NewForm::havingId( 'F1' )->build();

		$form->setGrammaticalFeatures( [ new ItemId( 'Q1' ), new ItemId( 'Q1' ) ] );

		$this->assertEquals( [ new ItemId( 'Q1' ) ], $form->getGrammaticalFeatures() );
	}

	public function testSetGrammaticalFeatures_AlphabeticallySortsItemIdsByTheirSerialization() {
		$form = NewForm::havingId( 'F1' )->build();

		$form->setGrammaticalFeatures( [ new ItemId( 'z:Q1' ), new ItemId( 'a:Q1' ) ] );

		$this->assertEquals(
			[ new ItemId( 'a:Q1' ), new ItemId( 'z:Q1' ) ],
			$form->getGrammaticalFeatures()
		);
	}

	public function testSetGrammaticalFeatures_NonItemIdIsGiven_ThrowsException() {
		$form = NewForm::havingId( 'F1' )->build();

		$this->setExpectedException( \InvalidArgumentException::class );
		$form->setGrammaticalFeatures( [ "Q1" ] );
	}

}
