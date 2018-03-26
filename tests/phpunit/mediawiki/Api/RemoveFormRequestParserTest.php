<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\Api\RemoveFormRequestParser;
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
			assertThat(
				$result->asFatalStatus()->getErrors(),
				hasItem( hasKeyValuePair( 'message', $expectedError ) )
			);
		}
	}

	public function provideInvalidParamsAndErrors() {
		return [
			'no id param' => [
				[],
				[ 'Parameter "[id]" is required' ]
			],
			'invalid id (random string not ID)' => [
				[ 'id' => 'foo' ],
				[ 'Parameter "[id]" expected to be a Form ID. Given: "foo"' ]
			],
		];
	}

	public function testFormIdPassedToRequestObject() {
		$parser = $this->newRemoveFormRequestParser();

		$result = $parser->parse( [ 'id' => 'L1-F2' ] );
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

}
