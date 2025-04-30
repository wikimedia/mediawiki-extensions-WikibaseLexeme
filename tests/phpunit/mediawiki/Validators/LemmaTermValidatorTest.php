<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Validators;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator
 *
 * @license GPL-2.0-or-later
 */
class LemmaTermValidatorTest extends TestCase {

	private const MAX_LENGTH = 10;

	/**
	 * @dataProvider provideValidTerms
	 */
	public function testValidValueGiven_ReturnsTrue( $validTerm ) {
		$validator = new LemmaTermValidator( self::MAX_LENGTH );

		$this->assertTrue( $validator->validate( $validTerm )->isValid() );
	}

	public static function provideValidTerms() {
		return [
			'simple' => [ 'foo' ],
			'cyrillic "х"' => [ 'х' ],
		];
	}

	/**
	 * @dataProvider provideInvalidTerms
	 */
	public function testInvalidValueGiven_ReturnsFalse( $invalidTerm ) {
		$validator = new LemmaTermValidator( self::MAX_LENGTH );

		$this->assertFalse( $validator->validate( $invalidTerm )->isValid() );
	}

	public static function provideInvalidTerms() {
		return [
			'not a string' => [ false ],
			'empty' => [ '' ],
			'exceeds maxLength' => [ str_repeat( 'x', self::MAX_LENGTH + 1 ) ],
			'leading whitespace' => [ ' foo' ],
			'trailing whitespace' => [ 'foo ' ],
		];
	}

}
