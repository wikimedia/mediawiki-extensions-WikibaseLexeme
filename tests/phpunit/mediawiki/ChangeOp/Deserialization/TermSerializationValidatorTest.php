<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\ChangeOp\Deserialization\TermSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\TermSerializationValidator
 *
 * @license GPL-2.0-or-later
 */
class TermSerializationValidatorTest extends TestCase {

	public function testGivenSimpleLanguageCode_passesToValidator() {
		$serialization = [ 'language' => 'en', 'value' => 'foo' ];
		$languageCode = 'en';

		$mockValidator = $this->getMockBuilder( TermChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$mockValidator->expects( $this->once() )
			->method( 'validateTermSerialization' )
			->with( $serialization, $languageCode );

		$validator = new TermSerializationValidator( $mockValidator );
		$validator->validate( $serialization, $languageCode );
	}

	public function testGivenExtendedLanguageCode_cutsExtension() {
		$serialization = [ 'language' => 'en-x-foo', 'value' => 'foo' ];
		$languageCode = 'en-x-foo';

		$mockValidator = $this->getMockBuilder( TermChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$mockValidator->expects( $this->once() )
			->method( 'validateTermSerialization' )
			->with( [ 'language' => 'en', 'value' => 'foo' ], 'en' );

		$validator = new TermSerializationValidator( $mockValidator );
		$validator->validate( $serialization, $languageCode );
	}

}
