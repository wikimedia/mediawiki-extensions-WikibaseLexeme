<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermLanguageValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermSerializationValidator;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Api\EditSenseElementsRequest;
use Wikibase\Lexeme\MediaWiki\Api\EditSenseElementsRequestParser;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\GlossesChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\EditSenseElementsRequestParser
 *
 * @license GPL-2.0-or-later
 */
class EditSenseElementsRequestParserTest extends TestCase {

	private const DEFAULT_GLOSS = 'furry animal';
	private const DEFAULT_GLOSS_LANGUAGE = 'en';
	private const DEFAULT_SENSE_ID = 'L1-S1';

	public function testSenseIdAndDataGetPassedToRequestObject() {
		$editSenseChangeOpDeserializer = $this->createMock( EditSenseChangeOpDeserializer::class );
		$editSenseChangeOpDeserializer->method( 'createEntityChangeOp' )
			->with( $this->getDataParams() )
			->willReturn( new ChangeOps() );

		$parser = new EditSenseElementsRequestParser(
			$this->newSenseIdDeserializer(),
			$editSenseChangeOpDeserializer
		);

		$request = $parser->parse( [
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => $this->getDataAsJson()
		] );

		$this->assertInstanceOf( EditSenseElementsRequest::class, $request );
		$this->assertSame( $request->getSenseId()->serialize(), self::DEFAULT_SENSE_ID );
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
			SenseId::PATTERN => static function ( $id ) {
				return new SenseId( $id );
			}
		] );
		return new SenseIdDeserializer( $idParser );
	}

	public function testBaseRevIdPassedToRequestObject() {
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
		$parser = new EditSenseElementsRequestParser(
			$this->newSenseIdDeserializer(),
			$editSenseChangeOpDeserializer
		);

		$request = $parser->parse(
			[ 'senseId' => 'L1-S1', 'baserevid' => 12345, 'data' => $this->getDataAsJson() ]
		);

		$this->assertSame( 12345, $request->getBaseRevId() );
	}

}
