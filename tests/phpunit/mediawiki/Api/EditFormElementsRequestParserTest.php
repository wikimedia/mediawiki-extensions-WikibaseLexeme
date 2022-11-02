<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\MediaWiki\Api\EditFormElementsRequest;
use Wikibase\Lexeme\MediaWiki\Api\EditFormElementsRequestParser;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\EditFormElementsRequestParser
 *
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequestParserTest extends TestCase {

	private const DEFAULT_REPRESENTATION = 'colour';
	private const DEFAULT_REPRESENTATION_LANGUAGE = 'en';
	private const DEFAULT_GRAMMATICAL_FEATURE = 'Q17';
	private const DEFAULT_FORM_ID = 'L1-F1';

	public function testFormIdAndDataGetPassedToRequestObject() {
		$parser = new EditFormElementsRequestParser(
			$this->newFormIdDeserializer(),
			$this->mockEditFormChangeOpDeserializer()
		);

		$request = $parser->parse( [
			'formId' => self::DEFAULT_FORM_ID,
			'data' => $this->getDataAsJson()
		] );

		$this->assertInstanceOf( EditFormElementsRequest::class, $request );
		$this->assertSame( $request->getFormId()->serialize(), self::DEFAULT_FORM_ID );
	}

	public function testBaseRevIdPassedToRequestObject() {
		$parser = new EditFormElementsRequestParser(
			$this->newFormIdDeserializer(),
			$this->mockEditFormChangeOpDeserializer()
		);

		$request = $parser->parse(
			[ 'formId' => 'L1-F1', 'baserevid' => 12345, 'data' => $this->getDataAsJson() ]
		);

		$this->assertSame( 12345, $request->getBaseRevId() );
	}

	public function testGivenEmptyData() {
		$this->expectException( ApiUsageException::class );

		$parser = new EditFormElementsRequestParser(
			$this->newFormIdDeserializer(),
			$this->mockEditFormChangeOpDeserializer()
		);

		$parser->parse(
			[ 'formId' => 'L1-F1', 'baserevid' => 12345, 'data' => "" ]
		);
	}

	public function testGivenInvalidData() {
		$this->expectException( ApiUsageException::class );

		$parser = new EditFormElementsRequestParser(
			$this->newFormIdDeserializer(),
			$this->mockEditFormChangeOpDeserializer()
		);

		$parser->parse(
			[ 'formId' => 'L1-F1', 'baserevid' => 12345, 'data' => "singleStringInsteadOfArray" ]
		);
	}

	private function getDataAsJson( array $dataToUse = [] ) {
		return json_encode( $this->getDataParams( $dataToUse ) );
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

	private function newFormIdDeserializer() {
		$idParser = new DispatchingEntityIdParser( [
			FormId::PATTERN => static function ( $id ) {
				return new FormId( $id );
			}
		] );
		return new FormIdDeserializer( $idParser );
	}

	private function mockEditFormChangeOpDeserializer() {
		$editFormChangeOpDeserializer = $this->createMock( EditFormChangeOpDeserializer::class );
		$editFormChangeOpDeserializer->method( 'createEntityChangeOp' )
			->with( $this->getDataParams() )
			->willReturn( new ChangeOps() );

		return $editFormChangeOpDeserializer;
	}
}
