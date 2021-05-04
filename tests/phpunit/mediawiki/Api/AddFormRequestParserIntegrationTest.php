<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMessage;
use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\AddFormRequestParser;
use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidFormClaims;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsNotAnItemId;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\MediaWiki\Api\Error\LanguageInconsistent;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeTermLanguageCanNotBeEmpty;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeTermTextCanNotBeEmpty;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\MediaWiki\Api\Error\UnknownLanguage;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddFormRequestParser
 *
 * @license GPL-2.0-or-later
 */
class AddFormRequestParserIntegrationTest extends TestCase {

	/**
	 * @dataProvider provideInvalidParamsAndErrors
	 */
	public function testGivenInvalidParams_parseReturnsError(
		array $params,
		array $expectedErrors
	) {
		$parser = $this->newAddFormRequestParser();

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
			'invalid lexeme ID (random string not ID)' => [
				[ 'lexemeId' => 'foo', 'data' => $this->getDataParam() ],
				[ [ 'parameterName' => 'lexemeId', 'fieldPath' => [] ], new ParameterIsNotLexemeId( 'foo' ) ]
			],
			'invalid lexeme ID (not a lexeme ID)' => [
				[ 'lexemeId' => 'Q11', 'data' => $this->getDataParam() ],
				[ [ 'parameterName' => 'lexemeId', 'fieldPath' => [] ], new ParameterIsNotLexemeId( 'Q11' ) ]
			],
			'data not a well-formed JSON' => [
				[ 'lexemeId' => 'L1', 'data' => '{foo' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '{foo' )
				]
			],
			'data not an object - string given' => [
				[ 'lexemeId' => 'L1', 'data' => '"foo"' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '"foo"' )
				]
			],
			'data not an object - array given' => [
				[ 'lexemeId' => 'L1', 'data' => '[]' ],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [] ],
					new ParameterIsNotAJsonObject( 'data', '[]' )
				]
			],
			'representations is a string' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'representations' => 'foo' ] )
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations' ] ],
					new JsonFieldHasWrongType( 'array', 'string' )
				]
			],
			'grammatical features not an array' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'grammaticalFeatures' => 'Q1' ] )
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'grammaticalFeatures' ] ],
					new JsonFieldHasWrongType( 'array', 'string' )
				]
			],
			'representation list contains only single empty representation' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
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
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
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
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
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
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
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
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
						[ 'representations' => [ 'foobar' => [ 'value' => 'goat', 'language' => 'foobar' ] ] ]
					)
				],
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'representations', 'foobar' ] ],
					new UnknownLanguage( 'foobar', 'goat' )
				]
			],
			'no representation string in data' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
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
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
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
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
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
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
						[ 'grammaticalFeatures' => [ 'L2' ] ]
					)
				] ,
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'grammaticalFeatures', 0 ] ],
					new JsonFieldIsNotAnItemId( 'L2' )
				]
			],
			'invalid form claims request (not an array)' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
						[ 'claims' => 'not an array' ]
					)
				] ,
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'claims' ] ],
					new JsonFieldHasWrongType( 'array', 'string' )
				]
			],
			'invalid form claims request (invalid serialization)' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
						[ 'claims' => [ [ 'invalid' ] ] ]
					)
				] ,
				[
					[ 'parameterName' => 'data', 'fieldPath' => [ 'claims' ] ],
					new InvalidFormClaims()
				]
			],
		];
	}

	public function testGivenInvalidClaimsRequest_throwException() {
		$parser = $this->newAddFormRequestParser();

		$this->expectException( ApiUsageException::class );
		$parser->parse( [
			'lexemeId' => 'L1',
			'data' => $this->getDataParam( [
				'claims' => 'not an array',
			] ),
		] );
	}

	private function getDataParam( array $dataToUse = [] ) {
		$simpleData = [
			'representations' => [
				'en' => [
					'language' => 'en',
					'value' => 'goat'
				]
			],
			'grammaticalFeatures' => [ 'Q17' ],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
	}

	private function newAddFormRequestParser() {
		$idParser = new DispatchingEntityIdParser( [
			ItemId::PATTERN => static function ( $id ) {
				return new ItemId( $id );
			},
			LexemeId::PATTERN => static function ( $id ) {
				return new LexemeId( $id );
			}
		] );

		$editFormChangeOpDeserializer = new EditFormChangeOpDeserializer(
			new RepresentationsChangeOpDeserializer(
				new TermDeserializer(),
				new StringNormalizer(),
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
				)
			),
			new ItemIdListDeserializer( new ItemIdParser() ),
			$this->newClaimsChangeOpDeserializer(),
			new CompositeValidator( [] )
		);

		return new AddFormRequestParser(
			$idParser,
			$editFormChangeOpDeserializer
		);
	}

	private function newClaimsChangeOpDeserializer() {
		return new ClaimsChangeOpDeserializer(
			WikibaseRepo::getExternalFormatStatementDeserializer(),
			WikibaseRepo::getChangeOpFactoryProvider()
				->getStatementChangeOpFactory()
		);
	}

}
