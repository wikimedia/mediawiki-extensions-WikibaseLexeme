<?php

namespace Wikibase\Lexeme\Tests\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Api\AddFormRequest;
use Wikibase\Lexeme\Api\AddFormRequestParserResult;
use Wikibase\Lexeme\Api\Error\ApiError;

/**
 * @covers \Wikibase\Lexeme\Api\AddFormRequestParserResult
 *
 * @license GPL-2.0-or-later
 */
class AddFormRequestParserResultTest extends TestCase {

	public function testGivenThereAreErrors_getRequestThrowsException() {
		$result = AddFormRequestParserResult::newWithErrors( [ $this->newApiError() ] );

		$this->setExpectedException( \Exception::class );

		$result->getRequest();
	}

	public function testGivenResultIsSuccessful_asFatalStatusThrowsException() {
		$result = AddFormRequestParserResult::newWithRequest( $this->newRequest() );

		$this->setExpectedException( \Exception::class );
		$result->asFatalStatus();
	}

	public function testGivenThereAreErrors_asFatalStatusReturnsFatalStatusWithTheErrorMessages() {
		$error1 = $this->newApiError();
		$error2 = $this->newApiError();
		$result = AddFormRequestParserResult::newWithErrors( [ $error1, $error2 ] );

		$status = $result->asFatalStatus();

		$this->assertTrue( !$status->isGood(), "Status is not fatal" );
		$gotErrors = $status->getErrors();
		assertThat( $gotErrors, hasItem( hasKeyValuePair( 'message', $error1->asApiMessage() ) ) );
		assertThat( $gotErrors, hasItem( hasKeyValuePair( 'message', $error2->asApiMessage() ) ) );
	}

	public function testCanNotCreateOnlyWithArrayContainingNotAnApiErrorInstance() {
		$this->setExpectedException( \InvalidArgumentException::class );
		AddFormRequestParserResult::newWithErrors( [ 'error' ] );
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
	 * @return AddFormRequest
	 */
	private function newRequest() {
		return $this->prophesize( AddFormRequest::class )->reveal();
	}

}
