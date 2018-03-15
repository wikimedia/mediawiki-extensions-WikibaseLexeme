<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\Api\Error\ParameterIsNotFormId;
use Wikibase\Lexeme\Api\RemoveFormRequestParser;
use Wikibase\Lexeme\Api\RemoveFormRequestParserResult;
use Wikibase\Lexeme\Api\Error\ApiError;
use Wikibase\Lexeme\Api\Error\ParameterIsRequired;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @covers \Wikibase\Lexeme\Api\RemoveFormRequestParser
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

		$result = $parser->parse( $params );

		$this->assertTrue( $result->hasErrors(), 'Result doesn\'t contain errors, but should' );
		foreach ( $expectedErrors as $expectedError ) {
			$this->assertResultContainsError( $result, $expectedError );
		}
	}

	public function provideInvalidParamsAndErrors() {
		return [
			'no formId param' => [
				[],
				[ new ParameterIsRequired( 'formId' ) ]
			],
			'invalid formId (random string not ID)' => [
				[ 'formId' => 'foo' ],
				[ new ParameterIsNotFormId( 'formId', 'foo' ) ]
			],
		];
	}

	public function testFormIdPassedToRequestObject() {
		$parser = $this->newRemoveFormRequestParser();

		$result = $parser->parse( [ 'formId' => 'L1-F2' ] );
		$request = $result->getRequest();

		$this->assertEquals( new FormId( 'L1-F2' ), $request->getFormId() );
	}

	private function newRemoveFormRequestParser() {
		$idParser = new DispatchingEntityIdParser( [
			FormId::PATTERN => function ( $id ) {
				return new FormId( $id );
			}
		] );

		return new RemoveFormRequestParser( $idParser );
	}

	private function assertResultContainsError(
		RemoveFormRequestParserResult $result,
		ApiError $expectedError
	) {
		$status = $result->asFatalStatus();
		$errors = $status->getErrors();

		assertThat(
			$errors,
			hasItem( hasKeyValuePair( 'message', $expectedError->asApiMessage() ) )
		);
	}

}
