<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Api\AddFormRequest;
use Wikibase\Lexeme\Api\AddFormRequestParser;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers \Wikibase\Lexeme\Api\AddFormRequestParser
 *
 * @license GPL-2.0-or-later
 */
class AddFormRequestParserTest extends TestCase {

	public function testGivenValidData_parseReturnsRequest() {
		$parser = $this->newAddFormRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertInstanceOf( AddFormRequest::class, $request );
	}

	public function testLexemeIdPassedToRequestObject() {
		$parser = $this->newAddFormRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertEquals( new LexemeId( 'L1' ), $request->getLexemeId() );
	}

	public function testFormDataPassedToRequestObject() {
		$parser = $this->newAddFormRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertEquals(
			new ChangeOpFormAdd(
				new ChangeOpFormEdit( [
					new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
					new ChangeOpGrammaticalFeatures( [ new ItemId( 'Q17' ) ] )
				] ),
				new GuidGenerator()
			),
			$request->getChangeOp()
		);
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
			ItemId::PATTERN => function ( $id ) {
				return new ItemId( $id );
			},
			LexemeId::PATTERN => function ( $id ) {
				return new LexemeId( $id );
			}
		] );

		$editFormChangeOpDeserializer = new EditFormChangeOpDeserializer(
			new RepresentationsChangeOpDeserializer(
				new TermDeserializer(),
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
				)
			),
			new ItemIdListDeserializer( new ItemIdParser() )
		);

		return new AddFormRequestParser(
			$idParser,
			$editFormChangeOpDeserializer
		);
	}

}
