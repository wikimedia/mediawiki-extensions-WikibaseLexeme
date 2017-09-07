<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\Lexeme\DataModel\FormId;

class FormIdTest extends \PHPUnit_Framework_TestCase {

	public function testCreateFormId_ValidSerializationIsGiven_CreatesIt() {
		$this->assertInstanceOf( FormId::class, new FormId( 'F1' ) );
	}

	public function testCreateFormId_IncorrectSerialization_ThrowsAnException() {
		$this->setExpectedException( \Exception::class );
		new FormId( 'F' );
	}

	public function testCreateFormId_GivenSerializationWithSpacesAround_ThrowsAnException() {
		$this->setExpectedException( \Exception::class );
		new FormId( '  F1  ' );
	}

}
