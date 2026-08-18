<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Infrastructure;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Infrastructure\TermLanguagesLemmaLanguageCodeValidator;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers \Wikibase\Lexeme\Infrastructure\TermLanguagesLemmaLanguageCodeValidator
 *
 * @license GPL-2.0-or-later
 */
class TermLanguagesLemmaLanguageCodeValidatorTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideValidLanguageCode
	 */
	public function testGivenValidLanguageCode_isValid( string $languageCode ): void {
		$this->assertTrue( $this->newValidator()->isValid( $languageCode ) );
	}

	public static function provideValidLanguageCode(): iterable {
		yield 'plain language code' => [ 'en' ];
		yield 'private use subtag with item id' => [ 'mis-x-Q123' ];
	}

	/**
	 * @dataProvider provideInvalidLanguageCode
	 */
	public function testGivenInvalidLanguageCode_isNotValid( string $languageCode ): void {
		$this->assertFalse( $this->newValidator()->isValid( $languageCode ) );
	}

	public static function provideInvalidLanguageCode(): iterable {
		yield 'unknown language' => [ 'xyz' ];
		yield 'empty string' => [ '' ];
		yield 'uppercase language' => [ 'EN' ];
		yield 'lowercase item id' => [ 'en-x-q123' ];
		yield 'property id' => [ 'en-x-P123' ];
		yield 'empty private use subtag' => [ 'en-x-' ];
		yield 'empty language with subtag' => [ '-x-Q1' ];
		yield 'repeated separator' => [ 'en-x-Q1-x-Q2' ];
	}

	private function newValidator(): TermLanguagesLemmaLanguageCodeValidator {
		return new TermLanguagesLemmaLanguageCodeValidator(
			new StaticContentLanguages( [ 'en', 'mis' ] )
		);
	}

}
