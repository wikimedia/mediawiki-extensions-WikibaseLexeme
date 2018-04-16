<?php

namespace Wikibase\Lexeme\Tests\Api;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\Api\Error\ApiError;
use Wikibase\Lexeme\Api\RemoveFormRequest;
use Wikibase\Lexeme\Api\RemoveFormRequestParserResult;

/**
 * @covers \Wikibase\Lexeme\Api\RemoveFormRequestParserResult
 *
 * @license GPL-2.0-or-later
 */
class RemoveFormRequestParserResultTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGivenThereAreErrors_getRequestThrowsException() {
		$result = RemoveFormRequestParserResult::newWithErrors( [ $this->newApiError() ] );

		$this->setExpectedException( \Exception::class );

		$result->getRequest();
	}

	public function testGivenResultIsSuccessful_asFatalStatusThrowsException() {
		$result = RemoveFormRequestParserResult::newWithRequest( $this->newRequest() );

		$this->setExpectedException( \Exception::class );
		$result->asFatalStatus();
	}

	public function testGivenThereAreErrors_asFatalStatusReturnsFatalStatusWithTheErrorMessages() {
		$error1 = $this->newApiError();
		$error2 = $this->newApiError();
		$result = RemoveFormRequestParserResult::newWithErrors( [ $error1, $error2 ] );

		$status = $result->asFatalStatus();

		$this->assertTrue( !$status->isGood(), "Status is not fatal" );
		$gotErrors = $status->getErrors();
		assertThat( $gotErrors, hasItem( hasKeyValuePair( 'message', $error1->asApiMessage() ) ) );
		assertThat( $gotErrors, hasItem( hasKeyValuePair( 'message', $error2->asApiMessage() ) ) );
	}

	public function testCanNotCreateOnlyWithArrayContainingNotAnApiErrorInstance() {
		$this->setExpectedException( \InvalidArgumentException::class );
		RemoveFormRequestParserResult::newWithErrors( [ 'error' ] );
	}

	/**
	 * @return ApiError
	 */
	private function newApiError() {
		/** @var ApiError|\Prophecy\Prophecy\ObjectProphecy $error */
		$error = $this->prophesize( ApiError::class );
		$error->asApiMessage()
			->willReturn( $this->prophesize( \ApiMessage::class )->reveal() );

		return $error->reveal();
	}

	/**
	 * @return RemoveFormRequest
	 */
	private function newRequest() {
		return $this->prophesize( RemoveFormRequest::class )->reveal();
	}

}
