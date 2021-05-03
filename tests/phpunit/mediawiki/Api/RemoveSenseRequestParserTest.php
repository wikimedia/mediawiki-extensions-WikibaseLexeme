<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMessage;
use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotSenseId;
use Wikibase\Lexeme\MediaWiki\Api\RemoveSenseRequestParser;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\RemoveSenseRequest
 *
 * @license GPL-2.0-or-later
 */
class RemoveSenseRequestParserTest extends TestCase {

	/**
	 * @dataProvider provideInvalidParamsAndErrors
	 */
	public function testGivenInvalidParams_parseReturnsError(
		array $params,
		array $expectedErrors
	) {
		$parser = $this->newRemoveSenseRequestParser();

		$expectedContext = $expectedErrors[0];
		$expectedError = $expectedErrors[1];
		$expectedMessage = $expectedError->asApiMessage( 'data', [] );

		try {
			$parser->parse( $params );
			$this->fail( 'Expected ApiUsageException did not occur.' );
		} catch ( ApiUsageException $exception ) {
			/** @var ApiMessage $message */
			$message = $exception->getMessageObject();

			$this->assertInstanceOf( ApiMessage::class, $message );

			$this->assertEquals( $expectedMessage->getKey(), $message->getKey() );
			$this->assertEquals( $expectedMessage->getApiCode(), $message->getApiCode() );
			$this->assertEquals( $expectedContext, $message->getApiData() );
		}
	}

	public function provideInvalidParamsAndErrors() {
		return [
			'invalid id (random string not ID)' => [
				[ 'id' => 'foo' ],
				[ [ 'parameterName' => 'id', 'fieldPath' => [] ], new ParameterIsNotSenseId( 'foo' ) ]
			],
			'invalid id (form id)' => [
				[ 'id' => 'L1-F2' ],
				[ [ 'parameterName' => 'id', 'fieldPath' => [] ], new ParameterIsNotSenseId( 'L1-F2' ) ]
			],
		];
	}

	public function testSenseIdPassedToRequestObject() {
		$parser = $this->newRemoveSenseRequestParser();

		$request = $parser->parse( [ 'id' => 'L1-S2' ] );

		$this->assertEquals( new SenseId( 'L1-S2' ), $request->getSenseId() );
	}

	/**
	 * @return RemoveSenseRequestParser
	 */
	private function newRemoveSenseRequestParser() {
		$idParser = new DispatchingEntityIdParser( [
			SenseId::PATTERN => static function ( $id ) {
				return new SenseId( $id );
			}
		] );

		return new RemoveSenseRequestParser( new SenseIdDeserializer( $idParser ) );
	}

	public function testBaseRevIdPassedToRequestObject() {
		$parser = $this->newRemoveSenseRequestParser();
		$request = $parser->parse(
			[ 'id' => 'L1-S2', 'baserevid' => 12345 ]
		);
		$this->assertEquals( 12345, $request->getBaseRevId() );
	}

}
