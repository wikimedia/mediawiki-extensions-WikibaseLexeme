<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Api\AddFormRequest;
use Wikibase\Lexeme\Api\AddFormRequestParser;
use Wikibase\Lexeme\Api\AddFormRequestParserResult;
use Wikibase\Lexeme\Api\Error\ApiError;
use Wikibase\Lexeme\Api\Error\FormMustHaveAtLeastOneRepresentation;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsNotAnItemId;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\JsonFieldMustNotBeEmpty;
use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\Api\Error\ParameterIsRequired;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\RepresentationsMustHaveUniqueLanguage;
use Wikibase\Lexeme\Api\Error\RepresentationTextCanNotBeEmpty;
use Wikibase\Lexeme\ChangeOp\ChangeOpAddForm;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Api\AddFormRequestParser
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class AddFormRequestParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideInvalidParamsAndErrors
	 */
	public function testGivenInvalidParams_parseReturnsError(
		array $params,
		array $expectedErrors
	) {
		$parser = $this->newAddFormRequestParser();

		$result = $parser->parse( $params );

		$this->assertTrue( $result->hasErrors(), 'Result doesnt contain errors, but should' );
		foreach ( $expectedErrors as $expectedError ) {
			$this->assertResultContainsError( $result, $expectedError );
		}
	}

	public function provideInvalidParamsAndErrors() {
		$noRepresentationsInDataParams = json_encode(
			[ 'grammaticalFeatures' => [] ]
		);
		$noGrammaticalFeaturesInDataParams = json_encode(
			[ 'representations' => [ 'language' => 'en', 'representation' => 'goat' ] ]
		);

		return [
			'no lexemeId param' => [
				[ 'data' => $this->getDataParam() ],
				[ new ParameterIsRequired( 'lexemeId' ) ]
			],
			'no data param' => [
				[ 'lexemeId' => 'L1' ],
				[ new ParameterIsRequired( 'data' ) ]
			],
			'invalid lexeme ID (random string not ID)' => [
				[ 'lexemeId' => 'foo', 'data' => $this->getDataParam() ],
				[ new ParameterIsNotLexemeId( 'lexemeId', 'foo' ) ]
			],
			'invalid lexeme ID (not a lexeme ID)' => [
				[ 'lexemeId' => 'Q11', 'data' => $this->getDataParam() ],
				[ new ParameterIsNotLexemeId( 'lexemeId', 'Q11' ) ]
			],
			'data not a well-formed JSON' => [
				[ 'lexemeId' => 'L1', 'data' => '{foo' ],
				[ new ParameterIsNotAJsonObject( 'data', '{foo' ) ]
			],
			'data not an object - string given' => [
				[ 'lexemeId' => 'L1', 'data' => '"foo"' ],
				[ new ParameterIsNotAJsonObject( 'data', '"foo"' ) ]
			],
			'data not an object - array given' => [
				[ 'lexemeId' => 'L1', 'data' => '[]' ],
				[ new ParameterIsNotAJsonObject( 'data', '[]' ) ]
			],
			'no representations in data' => [
				[ 'lexemeId' => 'L1', 'data' => $noRepresentationsInDataParams ],
				[ new JsonFieldIsRequired( 'data', [ 'representations' ] ) ]
			],
			'no grammatical features in data' => [
				[ 'lexemeId' => 'L1', 'data' => $noGrammaticalFeaturesInDataParams ],
				[ new JsonFieldIsRequired( 'data', [ 'grammaticalFeatures' ] ) ]
			],
			'representations is a string' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'representations' => 'foo' ] )
				],
				[ new JsonFieldHasWrongType( 'data', [ 'representations' ], 'array', 'string' ) ]
			],
			'representations is an object' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'representations' => [ 'foo' => 'bar' ] ] )
				],
				[ new JsonFieldHasWrongType( 'data', [ 'representations' ], 'array', 'object' ) ]
			],
			'grammatical features not an array' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'grammaticalFeatures' => 'Q1' ] )
				],
				[ new JsonFieldHasWrongType(
					'data', [ 'grammaticalFeatures' ], 'array', 'string'
				) ]
			],
			'empty representation list in data' => [
				[ 'lexemeId' => 'L1', 'data' => $this->getDataParam(
					[ 'representations' => [] ]
				) ],
				[ new FormMustHaveAtLeastOneRepresentation( 'data', [ 'representations' ] ) ]
			],
			'representation list contains only single empty representation' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
						[ 'representations' => [ [ 'representation' => '', 'language' => 'en' ] ] ]
					)
				],
				[ new RepresentationTextCanNotBeEmpty( 'data', [ 'representations', 0, 'representation' ] ) ]
			],
			'representation list contains only representation with empty language' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
						[ 'representations' => [ [ 'representation' => 'goat', 'language' => '' ] ] ]
					)
				],
				[ new RepresentationLanguageCanNotBeEmpty( 'data', [ 'representations', 0, 'language' ] ) ]
			],
			'no representation string in data' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
						[ 'representations' => [ [ 'language' => 'en' ] ] ]
					)
				],
				[ new JsonFieldIsRequired( 'data', [ 'representations', 0, 'representation' ] ) ]
			],
			'no representation language in data' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam(
						[ 'representations' => [ [ 'representation' => 'foo' ] ] ]
					)
				],
				[ new JsonFieldIsRequired( 'data', [ 'representations', 0, 'language' ] ) ]
			],
			'two representations with the same language' => [
				[
					'lexemeId' => 'L1',
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
					'lexemeId' => 'L1',
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
					'lexemeId' => 'L1',
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
					'language' => 'en',
					'representation' => ''
				]
			],
			'grammaticalFeatures' => [],
		];

		$parser = $this->newAddFormRequestParser();
		$result = $parser->parse( [ 'lexemeId' => 'L1', 'data' => json_encode( $data ) ] );

		$errors = $result->asFatalStatus()->getErrors();
		$this->assertCount( 1, $errors );
		$expectedError = new RepresentationTextCanNotBeEmpty(
			'data',
			[ 'representations', 0, 'representation' ]
		);
		$this->assertResultContainsError( $result, $expectedError );
	}

	public function testGivenValidData_parseReturnsRequestAndNoErrors() {
		$parser = $this->newAddFormRequestParser();

		$result = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertInstanceOf(
			AddFormRequest::class,
			$result->getRequest()
		);
		$this->assertFalse( $result->hasErrors() );
	}

	public function testLexemeIdPassedToRequestObject() {
		$parser = $this->newAddFormRequestParser();

		$result = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );
		$request = $result->getRequest();

		$this->assertEquals( new LexemeId( 'L1' ), $request->getLexemeId() );
	}

	public function testFormDataPassedToRequestObject() {
		$parser = $this->newAddFormRequestParser();

		$result = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );
		$request = $result->getRequest();

		$this->assertEquals(
			new ChangeOpAddForm( new TermList( [ new Term( 'en', 'goat' ) ] ), [ new ItemId( 'Q17' ) ] ),
			$request->getChangeOp()
		);
	}

	private function getDataParam( array $dataToUse = [] ) {
		$simpleData = [
			'representations' => [
				[
					'language' => 'en',
					'representation' => 'goat'
				]
			],
			'grammaticalFeatures' => [ 'Q17' ],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
	}

	private function newAddFormRequestParser() {
		$idParser = new DispatchingEntityIdParser( [
			ItemId::PATTERN => function ( $id ) {
				return new ItemId( $id );
			},
			LexemeId::PATTERN => function ( $id ) {
				return new LexemeId( $id );
			}
		] );

		return new AddFormRequestParser( $idParser );
	}

	private function assertResultContainsError(
		AddFormRequestParserResult $result,
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
