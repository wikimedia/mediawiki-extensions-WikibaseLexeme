<?php

namespace Wikibase\Lexeme\Tests\Api;

use Wikibase\Lexeme\Api\AddFormRequestParserResult;

/**
 * @covers \Wikibase\Lexeme\Api\AddFormRequestParserResult
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class AddFormRequestParserResultTest extends \PHPUnit_Framework_TestCase {

	public function testGivenThereAreErrors_getRequestThrowsException() {
		$result = AddFormRequestParserResult::newWithErrors( [ 'foobar' ] );

		$this->setExpectedException( \Exception::class );

		$result->getRequest();
	}

}
