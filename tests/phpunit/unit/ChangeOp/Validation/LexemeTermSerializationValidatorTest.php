<?php

namespace Wikibase\Lexeme\Tests\Unit\ChangeOp\Validation;

use PHPUnit\Framework\MockObject\MockObject;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\MediaWiki\Api\Error\ApiError;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\MediaWiki\Api\Error\LanguageInconsistent;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeTermTextCanNotBeEmpty;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator
 *
 * @license GPL-2.0-or-later
 */
class LexemeTermSerializationValidatorTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider notAnArrayProvider
	 */
	public function testGivenSerializationIsNotAnArray_addsViolation( $serialization ) {
		$validator = new LexemeTermSerializationValidator( $this->newLanguageValidator() );
		$context = $this->newValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'array', gettype( $serialization ) ) );

		$validator->validate( 'en', $serialization, $context );
	}

	public function testGivenLanguageKeyMissingInSerialization_addsViolation() {
		$validator = new LexemeTermSerializationValidator( $this->newLanguageValidator() );
		$context = $this->newValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldIsRequired( 'language' ) );

		$validator->validate( 'en', [ 'value' => 'potato' ], $context );
	}

	public function testGivenValueKeyMissingInSerialization_addsViolation() {
		$validator = new LexemeTermSerializationValidator( $this->newLanguageValidator() );
		$context = $this->newValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( new JsonFieldIsRequired( 'value' ) );

		$validator->validate( 'en', [ 'language' => 'en' ], $context );
	}

	public function testLanguageInconsistent_addsViolation() {
		$validator = new LexemeTermSerializationValidator( $this->newLanguageValidator() );
		$context = $this->newValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( new LanguageInconsistent( 'en-x-Q123', 'en' ) );

		$validator->validate( 'en-x-Q123', [ 'language' => 'en', 'value' => 'potato' ], $context );
	}

	public function testLanguageCodeIsValidated() {
		$languageValidator = $this->newLanguageValidator();
		$mockError = $this->getMockBuilder( ApiError::class )->getMock();
		$languageValidator->expects( $this->once() )
			->method( 'validate' )
			->willReturnCallback( function ( $lang, $context ) use ( $mockError ) {
				$context->addViolation( $mockError );
			} );
		$context = $this->newValidationContext();
		$context->expects( $this->once() )
			->method( 'addViolation' )
			->with( $mockError );

		( new LexemeTermSerializationValidator( $languageValidator ) )
			->validate( 'foo', [ 'language' => 'foo', 'value' => 'bar' ], $context );
	}

	/**
	 * @dataProvider notAStringProvider
	 */
	public function testGivenValueIsNotAString_addsViolation( $value ) {
		$validator = new LexemeTermSerializationValidator( $this->newLanguageValidator() );
		$context = $this->newValidationContext();
		$context->expects( $this->atLeastOnce() )
			->method( 'addViolation' )
			->with( new JsonFieldHasWrongType( 'string', gettype( $value ) ) );

		$validator->validate( 'qqq', [ 'language' => 'qqq', 'value' => $value ], $context );
	}

	public function testGivenEmptyValue_addsViolation() {
		$validator = new LexemeTermSerializationValidator( $this->newLanguageValidator() );
		$context = $this->newValidationContext();
		$context->expects( $this->atLeastOnce() )
			->method( 'addViolation' )
			->with( new LexemeTermTextCanNotBeEmpty() );

		$validator->validate( 'en', [ 'language' => 'en', 'value' => '' ], $context );
	}

	/**
	 * @dataProvider validTermProvider
	 */
	public function testGivenValidTermSerialization_addsNoViolations( $languageCode, $serialization ) {
		$validator = new LexemeTermSerializationValidator( $this->newLanguageValidator() );
		$context = $this->newValidationContext();
		$context->expects( $this->never() )
			->method( 'addViolation' );

		$validator->validate( $languageCode, $serialization, $context );
	}

	/**
	 * @return MockObject|LexemeTermLanguageValidator
	 */
	private function newLanguageValidator() {
		return $this->getMockBuilder( LexemeTermLanguageValidator::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return MockObject|ValidationContext
	 */
	private function newValidationContext() {
		return $this->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function notAnArrayProvider() {
		return [
			[ null ],
			[ false ],
			[ 'potato' ],
			[ 123 ],
		];
	}

	public function notAStringProvider() {
		return [
			[ null ],
			[ false ],
			[ [] ],
			[ 123 ],
		];
	}

	public function validTermProvider() {
		return [
			[ 'en', [ 'language' => 'en', 'value' => 'yay' ] ],
			[ 'en', [ 'language' => 'en', 'remove' => '' ] ],
		];
	}

}
