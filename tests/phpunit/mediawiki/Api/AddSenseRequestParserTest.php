<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\MediaWiki\Api\AddSenseRequest;
use Wikibase\Lexeme\MediaWiki\Api\AddSenseRequestParser;
use Wikibase\Lexeme\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\DataModel\LexemeId;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddSenseRequestParser
 *
 * @license GPL-2.0-or-later
 */
class AddSenseRequestParserTest extends TestCase {

	public function testGivenValidData_parseReturnsRequest() {
		$parser = $this->newAddSenseRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertInstanceOf( AddSenseRequest::class, $request );
	}

	public function testLexemeIdPassedToRequestObject() {
		$parser = $this->newAddSenseRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertEquals( new LexemeId( 'L1' ), $request->getLexemeId() );
	}

	public function testSenseDataPassedToRequestObject() {
		$parser = $this->newAddSenseRequestParser();

		$request = $parser->parse( [ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ] );

		$this->assertEquals(
			new ChangeOpSenseAdd(
				new ChangeOpSenseEdit( [
					new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
				] ),
				new GuidGenerator()
			),
			$request->getChangeOp()
		);
	}

	private function getDataParam( array $dataToUse = [] ) {
		$simpleData = [
			'glosses' => [
				'en' => [
					'language' => 'en',
					'value' => 'furry animal',
				],
			],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
	}

	private function newAddSenseRequestParser() {
		$idParser = new DispatchingEntityIdParser( [
			LexemeId::PATTERN => function ( $id ) {
				return new LexemeId( $id );
			}
		] );

		$editSenseChangeOpDeserializer = new EditSenseChangeOpDeserializer(
			new GlossesChangeOpDeserializer(
				new TermDeserializer(),
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
				)
			)
		);

		return new AddSenseRequestParser(
			$idParser,
			$editSenseChangeOpDeserializer
		);
	}

}
