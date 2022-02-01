<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\AddSenseRequest;
use Wikibase\Lexeme\MediaWiki\Api\AddSenseRequestParser;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;

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

	public function testBaseRevIdPassedToRequestObject() {
		$parser = $this->newAddSenseRequestParser();

		$request = $parser->parse(
			[ 'lexemeId' => 'L1', 'data' => $this->getDataParam(), 'baserevid' => 1 ]
		);

		$this->assertSame( 1, $request->getBaseRevId() );
	}

	public function testGivenEmptyData() {
		$this->expectException( ApiUsageException::class );

		$this->newAddSenseRequestParser()->parse( [ 'lexemeId' => 'L1', 'data' => "" ] );
	}

	public function testGivenInvalidData() {
		$this->expectException( ApiUsageException::class );

		$this->newAddSenseRequestParser()->parse( [ 'lexemeId' => 'L1', 'data' => "singleString" ] );
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
			LexemeId::PATTERN => static function ( $id ) {
				return new LexemeId( $id );
			}
		] );

		$editSenseChangeOpDeserializer = new EditSenseChangeOpDeserializer(
			new GlossesChangeOpDeserializer(
				new TermDeserializer(),
				new StringNormalizer(),
				new LexemeTermSerializationValidator(
					new LexemeTermLanguageValidator( new StaticContentLanguages( [ 'en', 'de' ] ) )
				)
			),
			$this->createStub( ClaimsChangeOpDeserializer::class )
		);

		return new AddSenseRequestParser(
			$idParser,
			$editSenseChangeOpDeserializer
		);
	}

}
