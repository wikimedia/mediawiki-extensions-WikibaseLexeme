<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\RestApi;

use Generator;
use MediaWiki\Rest\HttpException;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\RestApi\AssertValidTopLevelFields;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\RestApi\AssertValidTopLevelFields
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0-or-later
 */
class AssertValidTopLevelFieldsTest extends TestCase {
	use AssertValidTopLevelFields;

	/**
	 * @doesNotPerformAssertions
	 *
	 * @dataProvider validBodyProvider
	 */
	public function testValid( ?array $body, array $bodyParamSettings ): void {
		$this->assertValidTopLevelTypes( $body, $bodyParamSettings );
	}

	public static function validBodyProvider(): Generator {
		yield 'null body' => [ null, [] ];

		yield 'valid fields' => [
			[
				'stringy' => 'some string',
				'arrayy' => [],
				'booly' => true,
			],
			[
				'stringy' => [ ParamValidator::PARAM_TYPE => 'string' ],
				'arrayy' => [ ParamValidator::PARAM_TYPE => 'array' ],
				'booly' => [ ParamValidator::PARAM_TYPE => 'boolean' ],
			],
		];

		yield 'missing optional field' => [
			[ 'stringy' => 'some string' ],
			[
				'stringy' => [ ParamValidator::PARAM_TYPE => 'string', ParamValidator::PARAM_REQUIRED => true ],
				'arrayy' => [ ParamValidator::PARAM_TYPE => 'array', ParamValidator::PARAM_REQUIRED => false ],
			],
		];
	}

	/**
	 * @dataProvider invalidBodyProvider
	 */
	public function testInvalid( array $body, array $bodyParamSettings, HttpException $expectedException ): void {
		try {
			$this->assertValidTopLevelTypes( $body, $bodyParamSettings );
			$this->fail( 'expected exception was not thrown' );
		} catch ( HttpException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function invalidBodyProvider(): Generator {
		yield 'int not a string' => [
			[ 'stringy' => 123 ],
			[ 'stringy' => [ ParamValidator::PARAM_TYPE => 'string' ] ],
			new HttpException(
				"Invalid value at '/stringy'",
				400,
				[
					'code' => 'invalid-value',
					'context' => [ 'path' => '/stringy' ],
				]
			),
		];

		yield 'string not an array' => [
			[ 'arrayy' => 'not an array' ],
			[ 'arrayy' => [ ParamValidator::PARAM_TYPE => 'array' ] ],
			new HttpException(
				"Invalid value at '/arrayy'",
				400,
				[
					'code' => 'invalid-value',
					'context' => [ 'path' => '/arrayy' ],
				]
			),
		];

		yield 'missing top-level field' => [
			[],
			[ 'lexeme' => [ ParamValidator::PARAM_REQUIRED => true ] ],
			new HttpException(
				'Required field missing',
				400,
				[
					'code' => 'missing-field',
					'context' => [
						'path' => '',
						'field' => 'lexeme',
					],
				]
			),
		];
	}

}
