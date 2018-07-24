<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMessage;
use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\Api\EditSenseElementsRequestParser;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\LanguageInconsistent;
use Wikibase\Lexeme\Api\Error\LexemeTermLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\LexemeTermTextCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Api\Error\ParameterIsNotSenseId;
use Wikibase\Lexeme\Api\Error\UnknownLanguage;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers \Wikibase\Lexeme\Api\EditSenseElementsRequestParser
 *
 * @license GPL-2.0-or-later
 */
class EditSenseElementsRequestParserIntegrationTest extends TestCase {

	const DEFAULT_GLOSS = 'colour';
	const DEFAULT_GLOSS_LANGUAGE = 'en';
	const DEFAULT_SENSE_ID = 'L1-S1';

	/**
	 * @dataProvider provideInvalidParamsAndRespectiveErrors
	 */
	public function testGivenInvalidParams_parseReturnsError(
		array $params,
		array $expectedErrors
	) {
		$parser = $this->newRequestParser();

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

	public function provideInvalidParamsAndRespectiveErrors() {
		return [
			'invalid sense ID (random string not ID)' => [
				[ 'senseId' => 'foo', 'data' => $this->getDataAsJson() ],
				[
					[ 'parameterName' => 'senseId', 'fieldPath' => [] ],
					new ParameterIsNotSenseId( 'foo' )
				]
			],
			'invalid sense ID (not a sense ID)' => [
				[ 'senseId' => 'Q11', 'data' => $this->getDataAsJson() ],
				[
					[ 'parameterName' => 'senseId', 'fieldPath' => [] ],
					new ParameterIsNotSenseId( 'Q11' )
				]
			],
			'invalid sense ID (no lexeme part in the ID)' => [
				[ 'senseId' => 'S1', 'data' => $this->getDataAsJson() ],
				[
					[ 'parameterName' => 'senseId', 'fieldPath' => [] ],
					new ParameterIsNotSenseId( 'F1' )
				]
			],
			'data not a well-formed JSON' => [
				[ 'senseId' => self::DEFAULT_SENSE_ID, 'data' => '{foo' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '{foo' )
				]
			],
			'data not an object - string given' => [
				[ 'senseId' => self::DEFAULT_SENSE_ID, 'data' => '"foo"' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '"foo"' )
				]
			],
			'data not an object - array given' => [
				[ 'senseId' => self::DEFAULT_SENSE_ID, 'data' => '[]' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '[]' )
				]
			],
			'glosses is a string' => [
				[
					'senseId' => self::DEFAULT_SENSE_ID,
					'data' => $this->getDataAsJson( [ 'glosses' => 'foo' ] )
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'glosses' ] ],
					new JsonFieldHasWrongType( 'array', 'string' )
				]
			],
			'gloss list contains only single empty gloss' => [
				[
					'senseId' => self::DEFAULT_SENSE_ID,
					'data' => $this->getDataAsJson(
						[ 'glosses' => [ 'en' => [ 'value' => '', 'language' => 'en' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'glosses', 'en' ] ],
					new LexemeTermTextCanNotBeEmpty()
				]
			],
			'gloss list contains only gloss with empty language' => [
				[
					'senseId' => self::DEFAULT_SENSE_ID,
					'data' => $this->getDataAsJson(
						[ 'glosses' => [ 'en' => [ 'value' => 'furry animal', 'language' => '' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'glosses', 'en' ] ],
					new LanguageInconsistent( 'en', '' )
				]
			],
			'gloss list contains gloss with empty language key' => [
				[
					'senseId' => self::DEFAULT_SENSE_ID,
					'data' => $this->getDataAsJson(
						[ 'glosses' => [ '' => [ 'value' => 'furry animal', 'language' => 'en' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'glosses', '' ] ],
					new LexemeTermLanguageCanNotBeEmpty()
				]
			],
			'gloss list contains element with inconsistent language' => [
				[
					'senseId' => self::DEFAULT_SENSE_ID,
					'data' => $this->getDataAsJson(
						[ 'glosses' => [ 'en' => [ 'value' => 'furry animal', 'language' => 'de' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'glosses', 'en' ] ],
					new LanguageInconsistent(
						'en',
						'de'
					)
				]
			],
			'gloss list contains element with unknown language' => [
				[
					'senseId' => self::DEFAULT_SENSE_ID,
					'data' => $this->getDataAsJson(
						[ 'glosses' => [ 'foobar' => [ 'value' => 'furry animal', 'language' => 'foobar' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'glosses', 'foobar' ] ],
					new UnknownLanguage( 'foobar' )
				]
			],
			'no gloss string in data' => [
				[
					'senseId' => self::DEFAULT_SENSE_ID,
					'data' => $this->getDataAsJson(
						[ 'glosses' => [ 'en' => [ 'language' => 'en' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'glosses', 'en' ] ],
					new JsonFieldIsRequired( 'value' )
				]
			],
			'no gloss language in data' => [
				[
					'senseId' => self::DEFAULT_SENSE_ID,
					'data' => $this->getDataAsJson(
						[ 'glosses' => [ 'en' => [ 'value' => 'foo' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'glosses', 'en' ] ],
					new JsonFieldIsRequired( 'language' )
				]
			],
		];
	}

	private function getDataParams( array $dataToUse = [] ) {
		$simpleData = [
			'glosses' => [
				self::DEFAULT_GLOSS_LANGUAGE => [
					'language' => self::DEFAULT_GLOSS_LANGUAGE,
					'value' => self::DEFAULT_GLOSS,
				]
			],
		];

		return array_merge( $simpleData, $dataToUse );
	}

	private function getDataAsJson( array $dataToUse = [] ) {
		return json_encode( $this->getDataParams( $dataToUse ) );
	}

	private function newSenseIdDeserializer() {
		$idParser = new DispatchingEntityIdParser( [
			SenseId::PATTERN => function ( $id ) {
				return new SenseId( $id );
			}
		] );
		return new SenseIdDeserializer( $idParser );
	}

	private function newRequestParser() {
		$editSenseChangeOpDeserializer = new EditSenseChangeOpDeserializer(
			new GlossesChangeOpDeserializer(
				new TermDeserializer(),
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
				)
			)
		);

		return new EditSenseElementsRequestParser(
			$this->newSenseIdDeserializer(),
			$editSenseChangeOpDeserializer
		);
	}

}
