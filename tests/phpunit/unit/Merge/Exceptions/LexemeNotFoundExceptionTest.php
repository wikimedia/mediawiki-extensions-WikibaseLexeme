<?php

namespace Wikibase\Lexeme\Tests\Unit\Merge\Exceptions;

use MediaWikiUnitTestCase;
use Message;
use Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeNotFoundException;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Domain\Merge\Exceptions\LexemeNotFoundException
 *
 * @license GPL-2.0-or-later
 */
class LexemeNotFoundExceptionTest extends MediaWikiUnitTestCase {

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
