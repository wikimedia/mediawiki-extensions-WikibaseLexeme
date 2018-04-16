<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\Api\EditFormElementsRequest;
use Wikibase\Lexeme\Api\EditFormElementsRequestParserResult;
use Wikibase\Lexeme\Api\Error\ApiError;

/**
 * @covers \Wikibase\Lexeme\Api\EditFormElementsRequestParserResult
 *
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequestParserResultTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGivenThereAreErrors_getRequestThrowsException() {
		$result = EditFormElementsRequestParserResult::newWithErrors( [ $this->newApiError() ] );

		$this->setExpectedException( \Exception::class );

		$result->getRequest();
	}

	public function testGivenResultIsSuccessful_asFatalStatusThrowsException() {
		$result = EditFormElementsRequestParserResult::newWithRequest( $this->newRequest() );

		$this->setExpectedException( \Exception::class );
		$result->asFatalStatus();
	}

	public function testGivenThereAreErrors_asFatalStatusReturnsFatalStatusWithTheErrorMessages() {
		$error1 = $this->newApiError();
		$error2 = $this->newApiError();
		$result = EditFormElementsRequestParserResult::newWithErrors( [ $error1, $error2 ] );

		$status = $result->asFatalStatus();

		$this->assertTrue( !$status->isGood(), "Status is not fatal" );
		$gotErrors = $status->getErrors();
		assertThat( $gotErrors, hasItem( hasKeyValuePair( 'message', $error1->asApiMessage() ) ) );
		assertThat( $gotErrors, hasItem( hasKeyValuePair( 'message', $error2->asApiMessage() ) ) );
	}

	public function testCanNotCreateOnlyWithArrayContainingNotAnApiErrorInstance() {
		$this->setExpectedException( \InvalidArgumentException::class );
		EditFormElementsRequestParserResult::newWithErrors( [ 'error' ] );
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
	 * @return EditFormElementsRequest
	 */
	private function newRequest() {
		return $this->prophesize( EditFormElementsRequest::class )->reveal();
	}

}
