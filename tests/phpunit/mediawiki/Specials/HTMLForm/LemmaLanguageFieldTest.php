<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials\HTMLForm;

use HTMLForm;
use InvalidArgumentException;
use Message;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\LemmaLanguageField;
use Wikibase\Lexeme\WikibaseLexemeServices;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Specials\HTMLForm\LemmaLanguageField
 *
 * @license GPL-2.0-or-later
 */
class LemmaLanguageFieldTest extends TestCase {

	/**
	 * It already has its own options
	 * @dataProvider provideForbiddenConstructorParameters
	 */
	public function testConstructionWithForbiddenParametersFails( $param ) {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			"Cannot set options for content language field. It already has it's own options" );
		new LemmaLanguageField( [ $param => 'value' ] );
	}

	public function provideForbiddenConstructorParameters() {
		yield [ 'options' ];
		yield [ 'options-message' ];
		yield [ 'options-messages' ];
	}

	public function testValidateWithValidLanguageCodeReturnsTrue() {
		$field = $this->getWidget();
		$this->assertTrue( $field->validate( 'en', [] ) );
	}

	public function testValidateWithInvalidLanguageCodeReturnsErrorMessage() {
		$field = $this->getWidget();
		$errorMessage = $field->validate( 'NOT-A-LANGUAGE-CODE', [] );
		$this->assertInstanceOf( Message::class, $errorMessage );
		$this->assertSame(
			'wikibase-lexeme-lemma-language-not-recognized',
			$errorMessage->getKey()
		);
	}

	public function testOptionsAreBuildFromLanguagesAndFormatted() {
		$languages = WikibaseLexemeServices::getTermLanguages()->getLanguages();
		$field = $this->createPartialMock( LemmaLanguageField::class, [ 'msg' ] );
		$field->expects( $this->exactly( count( $languages ) ) )
			->method( 'msg' )
			->willReturnCallback( function ( $messageKey, $messageParams ) use ( $languages ) {
				$this->assertSame( 'wikibase-lexeme-lemma-language-option', $messageKey );
				$this->assertCount( 2, $messageParams );
				$this->assertContains( $messageParams[1], $languages );
				return new Message( $messageKey, $messageParams );
			} );
		$field->__construct( [ 'fieldname' => 'testfield', 'parent' => $this->newStubHtmlForm() ] );

		$options = $field->getOptions();
		$this->assertIsArray( $options );
		$this->assertCount( count( $languages ), $options );
	}

	private function getWidget( array $params = [] ) {
		$requiredParams = [
			'fieldname' => 'testfield',
			'parent' => $this->newStubHtmlForm(),
		];

		return new LemmaLanguageField( array_merge( $requiredParams, $params ) );
	}

	private function newStubHtmlForm(): HTMLForm {
		$form = $this->createStub( HTMLForm::class );
		$form->method( 'msg' )
			->willReturnCallback( 'wfMessage' );

		return $form;
	}

}
