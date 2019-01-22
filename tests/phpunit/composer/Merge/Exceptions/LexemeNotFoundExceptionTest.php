<?php

namespace Wikibase\Lexeme\Tests\Merge\Exceptions;

use Message;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeNotFoundException;

/**
 * @covers \Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeNotFoundException
 *
 * @license GPL-2.0-or-later
 */
class LexemeNotFoundExceptionTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGetErrorMessage() {
		$l404 = new LexemeId( 'L404' );
		$exception = new LexemeNotFoundException( $l404 );

		$this->assertEquals(
			new Message(
				'wikibase-lexeme-mergelexemes-error-lexeme-not-found',
				[ $l404->getSerialization() ]
			),
			$exception->getErrorMessage()
		);
	}

}
