<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiErrorFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Lexeme\Api\AddForm
 *
 * @license GPL-2.0+
 *
 * @group Wikibase
 * @group Database
 * @group medium
 * @group WikibaseLexeme
 */
class AddFormTest extends WikibaseApiTestCase {

	/**
	 * @dataProvider provideInvalidParams
	 */
	public function testGivenInvalidParameter_errorIsReturned(
		array $params,
		array $expectedError
	) {
		$this->setContentLang( 'qqq' );
		$params = array_merge(
			[ 'action' => 'wblexemeaddform' ],
			$params
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API error was raised' );
		} catch ( \ApiUsageException $e ) {
			/** @var \ApiMessage $message */
			$message = $e->getMessageObject();

			$this->assertInstanceOf( \ApiMessage::class, $message );
			$this->assertEquals( $expectedError['message-key'], $message->getKey(), 'Wrong message codes' );
			$this->assertEquals(
				$expectedError['message-parameters'],
				$message->getParams(),
				'Wrong message parameters'
			);
			$this->assertEquals(
				$expectedError['api-error-code'],
				$message->getApiCode(),
				'Wrong api code'
			);
			$this->assertEquals(
				$expectedError['api-error-data'],
				$message->getApiData(),
				'Wrong api data'
			);
		}
	}

	public function provideInvalidParams() {
		return [
			'no lexemeId param' => [
				[ 'data' => $this->getDataParam() ],
				[
					'message-key' => 'apierror-missingparam',
					'message-parameters' => [ 'lexemeId' ],
					'api-error-code' => 'nolexemeId',
					'api-error-data' => []
				],
			],
			'no data param' => [
				[ 'lexemeId' => 'L1' ],
				[
					'message-key' => 'apierror-missingparam',
					'message-parameters' => [ 'data' ],
					'api-error-code' => 'nodata',
					'api-error-data' => []
				],
			],
			'invalid lexeme ID (random string not ID)' => [
				[ 'lexemeId' => 'foo', 'data' => $this->getDataParam() ],
				[
					'message-key' => 'wikibase-lexeme-api-error-parameter-not-lexeme-id',
					'message-parameters' => [ 'lexemeId', 'foo' ],
					'api-error-code' => 'bad-request',
					'api-error-data' => []
				]
			],
			'data not a well-formed JSON object' => [
				[ 'lexemeId' => 'L1', 'data' => '{foo' ],
				[
					'message-key' => 'wikibase-lexeme-api-error-parameter-invalid-json-object',
					'message-parameters' => [ 'data', '{foo' ],
					'api-error-code' => 'bad-request',
					'api-error-data' => []
				],
			],
		];
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

	public function testGivenValidData_addsForm() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wblexemeaddform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$forms = $lexeme->getForms();

		$this->assertCount( 1, $lexeme->getForms() );
		$this->assertEquals( 'goat', $forms[0]->getRepresentations()->getByLanguage( 'en' )->getText() );
		$this->assertEquals( [ new ItemId( 'Q17' ) ], $forms[0]->getGrammaticalFeatures() );
	}

	public function testGivenValidData_responseContainsSuccessMarker() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wblexemeaddform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	private function saveLexeme( Lexeme $lexeme ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$store->saveEntity( $lexeme, self::class, $this->getMock( \User::class ) );
	}

	/**
	 * @param string $id
	 * @return Lexeme|null
	 */
	private function getLexeme( $id ) {
		$lookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
		return $lookup->getEntity( new LexemeId( $id ) );
	}

}
