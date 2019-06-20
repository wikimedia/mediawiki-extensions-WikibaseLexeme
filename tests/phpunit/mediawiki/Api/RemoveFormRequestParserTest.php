<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMessage;
use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotFormId;
use Wikibase\Lexeme\MediaWiki\Api\RemoveFormRequestParser;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\RemoveFormRequestParser
 *
 * @license GPL-2.0-or-later
 */
class RemoveFormRequestParserTest extends TestCase {

	/**
	 * @dataProvider provideInvalidParamsAndErrors
	 */
	public function testGivenInvalidParams_parseReturnsError(
		array $params,
		array $expectedErrors
	) {
		$parser = $this->newRemoveFormRequestParser();

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
				[ [ 'parameterName' => 'id', 'fieldPath' => [] ], new ParameterIsNotFormId( 'foo' ) ]
			],
			'invalid id (sense ID)' => [
				[ 'id' => 'L1-S2' ],
				[ [ 'parameterName' => 'id', 'fieldPath' => [] ], new ParameterIsNotFormId( 'L1-S2' ) ]
			],
		];
	}

	public function testFormIdPassedToRequestObject() {
		$parser = $this->newRemoveFormRequestParser();

		$request = $parser->parse( [ 'id' => 'L1-F2' ] );

		$this->assertEquals( new FormId( 'L1-F2' ), $request->getFormId() );
	}

	/**
	 * @return RemoveFormRequestParser
	 */
	private function newRemoveFormRequestParser() {
		$idParser = new DispatchingEntityIdParser( [
			FormId::PATTERN => static function ( $id ) {
				return new FormId( $id );
			}
		] );

		return new RemoveFormRequestParser( new FormIdDeserializer( $idParser ) );
	}

	public function testBaseRevIdPassedToRequestObject() {
		$parser = $this->newRemoveFormRequestParser();

		$request = $parser->parse(
			[ 'id' => 'L1-F2', 'baserevid' => 12345 ]
		);

		$this->assertSame( 12345, $request->getBaseRevId() );
	}

}
