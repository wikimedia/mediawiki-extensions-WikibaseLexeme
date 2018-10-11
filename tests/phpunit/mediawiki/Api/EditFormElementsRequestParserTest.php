<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\Lexeme\MediaWiki\Api\EditFormElementsRequest;
use Wikibase\Lexeme\MediaWiki\Api\EditFormElementsRequestParser;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\EditFormElementsRequestParser
 *
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequestParserTest extends TestCase {

	const DEFAULT_REPRESENTATION = 'colour';
	const DEFAULT_REPRESENTATION_LANGUAGE = 'en';
	const DEFAULT_GRAMMATICAL_FEATURE = 'Q17';
	const DEFAULT_FORM_ID = 'L1-F1';

	public function testFormIdAndDataGetPassedToRequestObject() {
		$editFormChangeOpDeserializer = $this
			->getMockBuilder( EditFormChangeOpDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
		$editFormChangeOpDeserializer
			->method( 'createEntityChangeOp' )
			->with( $this->getDataParams() )
			->willReturn( new ChangeOps() );

		$parser = new EditFormElementsRequestParser(
			$this->newFormIdDeserializer(),
			$editFormChangeOpDeserializer
		);

		$request = $parser->parse( [
			'formId' => self::DEFAULT_FORM_ID,
			'data' => $this->getDataAsJson()
		] );

		$this->assertInstanceOf( EditFormElementsRequest::class, $request );
		$this->assertSame( $request->getFormId()->serialize(), self::DEFAULT_FORM_ID );
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

	private function getDataAsJson( array $dataToUse = [] ) {
		return json_encode( $this->getDataParams( $dataToUse ) );
	}

	private function newFormIdDeserializer() {
		$idParser = new DispatchingEntityIdParser( [
			FormId::PATTERN => function ( $id ) {
				return new FormId( $id );
			}
		] );
		return new FormIdDeserializer( $idParser );
	}

}
