<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Api\AddFormRequest;
use Wikibase\Lexeme\Api\AddFormRequestParser;
use Wikibase\Lexeme\ChangeOp\ChangeOpAddForm;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @covers Wikibase\Lexeme\Api\AddFormRequestParser
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class AddFormRequestParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideInvalidParamsAndErrors
	 */
	public function testGivenInvalidParams_parseReturnsError( array $params ) {
		$parser = $this->newAddFormRequestParser();

		$result = $parser->parse( $params );

		$this->assertTrue( $result->hasErrors() );
	}

	public function provideInvalidParamsAndErrors() {
		$noRepresentationsInDataParams = json_encode(
			[ 'grammaticalFeatures' => [] ]
		);
		$noGrammaticalFeaturesInDataParams = json_encode(
			[ 'representations' => [ 'language' => 'en', 'representation' => 'goat' ] ]
		);

		return [
			'no lexemeId param' => [ [ 'data' => $this->getDataParam() ] ],
			'no data param' => [ [ 'lexemeId' => 'L1' ] ],
			'invalid lexeme ID (random string not ID)' => [ [
				'lexemeId' => 'foo', 'data' => $this->getDataParam()
			] ],
			'invalid lexeme ID (not a lexeme ID)' => [ [
				'lexemeId' => 'Q11', 'data' => $this->getDataParam()
			] ],
			'data not a well-formed JSON' => [ [ 'lexemeId' => 'L1', 'data' => '{foo' ] ],
			'data not an array' => [ [ 'lexemeId' => 'L1', 'data' => 'foo' ] ],
			'no representations in data' => [ [
				'lexemeId' => 'L1', 'data' => $noRepresentationsInDataParams
			] ],
			'no grammatical features in data' => [ [
				'lexemeId' => 'L1', 'data' => $noGrammaticalFeaturesInDataParams
			] ],
			'representations not an array' => [ [
				'lexemeId' => 'L1', 'data' => $this->getDataParam( [ 'representations' => 'foo' ] )
			] ],
			'grammatical features not an array' => [ [
				'lexemeId' => 'L1', 'data' => $this->getDataParam( [ 'grammaticalFeatures' => 'Q1' ] )
			] ],
			'empty representation list in data' => [ [
				'lexemeId' => 'L1',
				'data' => $this->getDataParam( [ 'representations' => [] ] )
			] ],
			'no representation string in data' => [ [
				'lexemeId' => 'L1',
				'data' => $this->getDataParam( [ 'representations' => [ [ 'language' => 'en' ] ] ] )
			] ],
			'no representation language in data' => [ [
				'lexemeId' => 'L1',
				'data' => $this->getDataParam( [ 'representations' => [ [ 'representation' => 'foo' ] ] ] )
			] ],
			'invalid item ID as grammatical feature (random string not ID)' => [ [
				'lexemeId' => 'L1',
				'data' => $this->getDataParam(
					[ 'representations' => [ [ 'grammaticalFeatures' => [ 'foo' ] ] ] ]
				)
			] ],
			'invalid item ID as grammatical feature (not an item ID)' => [ [
				'lexemeId' => 'L1',
				'data' => $this->getDataParam(
					[ 'representations' => [ [ 'grammaticalFeatures' => [ 'L2' ] ] ] ]
				)
			] ],
		];
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

}
