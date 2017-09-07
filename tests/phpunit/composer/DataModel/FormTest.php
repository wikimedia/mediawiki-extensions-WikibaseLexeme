<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;

class FormTest extends PHPUnit_Framework_TestCase {

	public function testCreateFormWithoutRepresentations_ThrowsAnException() {
		$this->setExpectedException( \Exception::class );
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
		$this->setExpectedException( \Exception::class );
		new Form(
			new FormId( 'F1' ),
			new TermList( [ new Term( 'en', 'representation' ) ] ),
			[ 1 ]
		);
	}

}
