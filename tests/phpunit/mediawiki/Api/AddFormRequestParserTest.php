<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\AddFormRequest;
use Wikibase\Lexeme\MediaWiki\Api\AddFormRequestParser;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\RepresentationsChangeOpDeserializer;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\Validators\CompositeValidator;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddFormRequestParser
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
				] )
			),
			$request->getChangeOp()
		);
	}

	public function testBaseRevIdPassedToRequestObject() {
		$parser = $this->newAddFormRequestParser();

		$request = $parser->parse(
			[ 'lexemeId' => 'L1', 'data' => $this->getDataParam(), 'baserevid' => 1 ]
		);

		$this->assertSame( 1, $request->getBaseRevId() );
	}

	public function testGivenEmptyData() {
		$this->expectException( ApiUsageException::class );

		$this->newAddFormRequestParser()->parse(
			[ 'lexemeId' => 'L1', 'data' => "" ]
		);
	}

	public function testGivenInvalidData() {
		$this->expectException( ApiUsageException::class );

		$this->newAddFormRequestParser()->parse(
			[ 'lexemeId' => 'L1', 'data' => "singleStringInsteadOfArray" ]
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
			$this->createMock( ClaimsChangeOpDeserializer::class ),
			new CompositeValidator( [] )
		);

		return new AddFormRequestParser(
			$idParser,
			$editFormChangeOpDeserializer
		);
	}

}
