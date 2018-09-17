<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMessage;
use ApiUsageException;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\Api\EditFormElementsRequestParser;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsNotAnItemId;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\LanguageInconsistent;
use Wikibase\Lexeme\Api\Error\LexemeTermLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\LexemeTermTextCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Api\Error\ParameterIsNotFormId;
use Wikibase\Lexeme\Api\Error\UnknownLanguage;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;

/**
 * @covers \Wikibase\Lexeme\Api\EditFormElementsRequestParser
 *
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequestParserIntegrationTest extends TestCase {

	use PHPUnit4And6Compat;

	const DEFAULT_REPRESENTATION = 'colour';
	const DEFAULT_REPRESENTATION_LANGUAGE = 'en';
	const DEFAULT_GRAMMATICAL_FEATURE = 'Q17';
	const DEFAULT_FORM_ID = 'L1-F1';

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
			'invalid form ID (random string not ID)' => [
				[ 'formId' => 'foo', 'data' => $this->getDataAsJson() ],
				[
					[ 'parameterName' => 'formId', 'fieldPath' => [] ],
					new ParameterIsNotFormId( 'foo' )
				]
			],
			'invalid form ID (not a form ID)' => [
				[ 'formId' => 'Q11', 'data' => $this->getDataAsJson() ],
				[
					[ 'parameterName' => 'formId', 'fieldPath' => [] ],
					new ParameterIsNotFormId( 'Q11' )
				]
			],
			'invalid form ID (no lexeme part in the ID)' => [
				[ 'formId' => 'F1', 'data' => $this->getDataAsJson() ],
				[
					[ 'parameterName' => 'formId', 'fieldPath' => [] ],
					new ParameterIsNotFormId( 'F1' )
				]
			],
			'data not a well-formed JSON' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => '{foo' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '{foo' )
				]
			],
			'data not an object - string given' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => '"foo"' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '"foo"' )
				]
			],
			'data not an object - array given' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => '[]' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '[]' )
				]
			],
			'representations is a string' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson( [ 'representations' => 'foo' ] )
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations' ] ],
					new JsonFieldHasWrongType( 'array', 'string' )
				]
			],
			'grammatical features not an array' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson( [ 'grammaticalFeatures' => 'Q1' ] )
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'grammaticalFeatures' ] ],
					new JsonFieldHasWrongType( 'array', 'string' )
				]
			],
			'representation list contains only single empty representation' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'representations' => [ 'en' => [ 'value' => '', 'language' => 'en' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations', 'en' ] ],
					new LexemeTermTextCanNotBeEmpty()
				]
			],
			'representation list contains only representation with empty language' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'representations' => [ 'en' => [ 'value' => 'goat', 'language' => '' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations', 'en' ] ],
					new LanguageInconsistent( 'en', '' )
				]
			],
			'representation list contains representation with empty language key' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'representations' => [ '' => [ 'value' => 'goat', 'language' => 'en' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations', '' ] ],
					new LexemeTermLanguageCanNotBeEmpty()
				]
			],
			'representation list contains element with inconsistent language' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'representations' => [ 'en' => [ 'value' => 'goat', 'language' => 'de' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations', 'en' ] ],
					new LanguageInconsistent(
						'en',
						'de'
					)
				]
			],
			'representation list contains element with unknown language' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'representations' => [ 'foobar' => [ 'value' => 'goat', 'language' => 'foobar' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations', 'foobar' ] ],
					new UnknownLanguage( 'foobar' )
				]
			],
			'no representation string in data' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'representations' => [ 'en' => [ 'language' => 'en' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations', 'en' ] ],
					new JsonFieldIsRequired( 'value' )
				]
			],
			'no representation language in data' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'representations' => [ 'en' => [ 'value' => 'foo' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations', 'en' ] ],
					new JsonFieldIsRequired( 'language' )
				]
			],
			'invalid item ID as grammatical feature (random string not ID)' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'grammaticalFeatures' => [ 'foo' ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'grammaticalFeatures', 0 ] ],
					new JsonFieldIsNotAnItemId( 'foo' )
				]
			],
			'invalid item ID as grammatical feature (not an item ID)' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataAsJson(
						[ 'grammaticalFeatures' => [ 'L2' ] ]
					)
				] ,
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'grammaticalFeatures', 0 ] ],
					new JsonFieldIsNotAnItemId( 'L2' )
				]
			],
		];
	}

	private function getDataParams( array $dataToUse = [] ) {
		$simpleData = [
			'representations' => [
				self::DEFAULT_REPRESENTATION_LANGUAGE => [
					'language' => self::DEFAULT_REPRESENTATION_LANGUAGE,
					'value' => self::DEFAULT_REPRESENTATION,
				]
			],
			'grammaticalFeatures' => [ self::DEFAULT_GRAMMATICAL_FEATURE ],
		];

		return array_merge( $simpleData, $dataToUse );
	}

	private function getDataAsJson( array $dataToUse = [] ) {
		return json_encode( $this->getDataParams( $dataToUse ) );
	}

	private function newFormIdDeserializer() {
		$idParser = new DispatchingEntityIdParser( [
			FormId::PATTERN => function ( $id ) {
				return new FormId( $id );
			}
		] );
		return new FormIdDeserializer( $idParser );
	}

	private function newRequestParser() {
		$editFormChangeOpDeserializer = new EditFormChangeOpDeserializer(
			new RepresentationsChangeOpDeserializer(
				new TermDeserializer(),
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
				)
			),
			new ItemIdListDeserializer( new ItemIdParser() ),
			$this->createMock( ClaimsChangeOpDeserializer::class )
		);

		return new EditFormElementsRequestParser(
			$this->newFormIdDeserializer(),
			$editFormChangeOpDeserializer
		);
	}

}
