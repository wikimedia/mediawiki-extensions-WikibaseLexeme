<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Api\EditFormElementsRequestParser;
use Wikibase\Lexeme\Api\EditFormElementsRequestParserResult;
use Wikibase\Lexeme\Api\Error\ApiError;
use Wikibase\Lexeme\Api\Error\FormMustHaveAtLeastOneRepresentation;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsNotAnItemId;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Api\Error\ParameterIsNotFormId;
use Wikibase\Lexeme\Api\Error\ParameterIsRequired;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\RepresentationsMustHaveUniqueLanguage;
use Wikibase\Lexeme\Api\Error\RepresentationTextCanNotBeEmpty;
use Wikibase\Lexeme\ChangeOp\ChangeOpEditFormElements;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @covers \Wikibase\Lexeme\Api\EditFormElementsRequestParser
 *
 * @license GPL-2.0+
 */
class EditFormElementsRequestParserTest extends TestCase {

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

		$result = $parser->parse( $params );

		$this->assertTrue( $result->hasErrors(), 'Result doesn not contain errors, but should' );
		foreach ( $expectedErrors as $expectedError ) {
			$this->assertResultContainsError( $result, $expectedError );
		}
	}

	public function provideInvalidParamsAndRespectiveErrors() {
		$noRepresentationsInDataParams = json_encode(
			[ 'grammaticalFeatures' => [] ]
		);
		$noGrammaticalFeaturesInDataParams = json_encode(
			[ 'representations' => [ 'language' => 'en', 'representation' => 'goat' ] ]
		);

		return [
			'no formId param' => [
				[ 'data' => $this->getDataParam() ],
				[ new ParameterIsRequired( 'formId' ) ]
			],
			'no data param' => [
				[ 'formId' => self::DEFAULT_FORM_ID ],
				[ new ParameterIsRequired( 'data' ) ]
			],
			'invalid form ID (random string not ID)' => [
				[ 'formId' => 'foo', 'data' => $this->getDataParam() ],
				[ new ParameterIsNotFormId( 'formId', 'foo' ) ]
			],
			'invalid form ID (not a form ID)' => [
				[ 'formId' => 'Q11', 'data' => $this->getDataParam() ],
				[ new ParameterIsNotFormId( 'formId', 'Q11' ) ]
			],
			'invalid form ID (no lexeme part in the ID)' => [
				[ 'formId' => 'F1', 'data' => $this->getDataParam() ],
				[ new ParameterIsNotFormId( 'formId', 'F1' ) ]
			],
			'data not a well-formed JSON' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => '{foo' ],
				[ new ParameterIsNotAJsonObject( 'data', '{foo' ) ]
			],
			'data not an object - string given' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => '"foo"' ],
				[ new ParameterIsNotAJsonObject( 'data', '"foo"' ) ]
			],
			'data not an object - array given' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => '[]' ],
				[ new ParameterIsNotAJsonObject( 'data', '[]' ) ]
			],
			'no representations in data' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => $noRepresentationsInDataParams ],
				[ new JsonFieldIsRequired( 'data', [ 'representations' ] ) ]
			],
			'no grammatical features in data' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => $noGrammaticalFeaturesInDataParams ],
				[ new JsonFieldIsRequired( 'data', [ 'grammaticalFeatures' ] ) ]
			],
			'representations is a string' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam( [ 'representations' => 'foo' ] )
				],
				[ new JsonFieldHasWrongType( 'data', [ 'representations' ], 'array', 'string' ) ]
			],
			'representations is an object' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam( [ 'representations' => [ 'foo' => 'bar' ] ] )
				],
				[ new JsonFieldHasWrongType( 'data', [ 'representations' ], 'array', 'object' ) ]
			],
			'grammatical features not an array' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam( [ 'grammaticalFeatures' => 'Q1' ] )
				],
				[ new JsonFieldHasWrongType(
					'data', [ 'grammaticalFeatures' ], 'array', 'string'
				) ]
			],
			'empty representation list in data' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => $this->getDataParam(
					[ 'representations' => [] ]
				) ],
				[ new FormMustHaveAtLeastOneRepresentation( 'data', [ 'representations' ] ) ]
			],
			'representation list contains only single empty representation' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'representations' => [ [ 'representation' => '', 'language' => 'en' ] ] ]
					)
				],
				[ new RepresentationTextCanNotBeEmpty( 'data', [ 'representations', 0, 'representation' ] ) ]
			],
			'representation list contains only representation with empty language' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'representations' => [ [ 'representation' => 'goat', 'language' => '' ] ] ]
					)
				],
				[ new RepresentationLanguageCanNotBeEmpty( 'data', [ 'representations', 0, 'language' ] ) ]
			],
			'no representation string in data' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'representations' => [ [ 'language' => 'en' ] ] ]
					)
				],
				[ new JsonFieldIsRequired( 'data', [ 'representations', 0, 'representation' ] ) ]
			],
			'no representation language in data' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'representations' => [ [ 'representation' => 'foo' ] ] ]
					)
				],
				[ new JsonFieldIsRequired( 'data', [ 'representations', 0, 'language' ] ) ]
			],
			'two representations with the same language' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'representations' => [
							[ 'representation' => 'r1',  'language' => 'en' ],
							[ 'representation' => 'r2',  'language' => 'fr' ],
							[ 'representation' => 'r3',  'language' => 'en' ],
						] ]
					)
				],
				[ new RepresentationsMustHaveUniqueLanguage(
					'data',
					[ 'representations', 2, 'language' ],
					'en'
				) ]
			],
			'invalid item ID as grammatical feature (random string not ID)' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'grammaticalFeatures' => [ 'foo' ] ]
					)
				],
				[ new JsonFieldIsNotAnItemId(
					'data',
					[ 'grammaticalFeatures', 0 ],
					'foo'
				) ]
			],
			'invalid item ID as grammatical feature (not an item ID)' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'grammaticalFeatures' => [ 'L2' ] ]
					)
				] ,
				[ new JsonFieldIsNotAnItemId(
					'data',
					[ 'grammaticalFeatures', 0 ],
					'L2'
				) ] ],
		];
	}

	public function testGivenOneRepresentationMissingText_parseReturnsRequestWithOnlyThisError() {
		$data = [
			'representations' => [
				[
					'language' => self::DEFAULT_REPRESENTATION_LANGUAGE,
					'representation' => ''
				]
			],
			'grammaticalFeatures' => [],
		];

		$parser = $this->newRequestParser();
		$result = $parser->parse( [ 'formId' => self::DEFAULT_FORM_ID, 'data' => json_encode( $data ) ] );

		$errors = $result->asFatalStatus()->getErrors();
		$this->assertCount( 1, $errors );
		$expectedError = new RepresentationTextCanNotBeEmpty(
			'data',
			[ 'representations', 0, 'representation' ]
		);
		$this->assertResultContainsError( $result, $expectedError );
	}

	public function testGivenValidData_parseReturnsRequestAndNoErrors() {
		$parser = $this->newRequestParser();

		$result = $parser->parse( [
			'formId' => self::DEFAULT_FORM_ID,
			'data' => $this->getDataParam()
		] );

		$this->assertFalse( $result->hasErrors() );
	}

	public function testFormIdGetsPassedToRequestObject() {
		$parser = $this->newRequestParser();

		$result = $parser->parse( [
			'formId' => self::DEFAULT_FORM_ID,
			'data' => $this->getDataParam()
		] );
		$request = $result->getRequest();

		$this->assertEquals( new FormId( self::DEFAULT_FORM_ID ), $request->getFormId() );
	}

	public function testFormDataGetsPassedToRequestObject() {
		$parser = $this->newRequestParser();

		$result = $parser->parse( [
			'formId' => self::DEFAULT_FORM_ID,
			'data' => $this->getDataParam()
		] );
		$request = $result->getRequest();

		$this->assertEquals(
			new ChangeOpEditFormElements(
				new TermList(
					[ new Term( self::DEFAULT_REPRESENTATION_LANGUAGE, self::DEFAULT_REPRESENTATION ) ]
				),
				[ new ItemId( self::DEFAULT_GRAMMATICAL_FEATURE ) ]
			),
			$request->getChangeOp()
		);
	}

	private function getDataParam( array $dataToUse = [] ) {
		$simpleData = [
			'representations' => [
				[
					'language' => self::DEFAULT_REPRESENTATION_LANGUAGE,
					'representation' => self::DEFAULT_REPRESENTATION,
				]
			],
			'grammaticalFeatures' => [ self::DEFAULT_GRAMMATICAL_FEATURE ],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
	}

	private function newRequestParser() {
		$idParser = new DispatchingEntityIdParser( [
			FormId::PATTERN => function ( $id ) {
				return new FormId( $id );
			}
		] );

		return new EditFormElementsRequestParser( $idParser );
	}

	private function assertResultContainsError(
		EditFormElementsRequestParserResult $result,
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
