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
	public function testGivenInvalidParameter_errorIsReturned( array $params, $expectedError ) {
		$params = array_merge(
			[ 'action' => 'wblexemeaddform' ],
			$params
		);

		// TODO: this is ugly but apparently WikibaseApiTestCase always uses 'en' message reporter
		// so uselang/errorlang set to qqx are ignored so it is needed to use en messages in asserts
		if ( is_string( $expectedError ) ) {
			$msgKey = $expectedError;
			$msgParams = [];
		} else {
			$msgKey = array_shift( $expectedError );
			$msgParams = $expectedError;
		}
		$errorMessage = new \Message( $msgKey, $msgParams, new \Language( 'en' ) );

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API error raised' );
		} catch ( \ApiUsageException $e ) {
			$this->assertEquals( ApiErrorFormatter::stripMarkup( $errorMessage->text() ), $e->getMessage() );
		}
	}

	public function provideInvalidParams() {
		$noRepresentationsInDataParams = json_encode(
			[ 'grammaticalFeatures' => [] ]
		);
		$noGrammaticalFeaturesInDataParams = json_encode(
			[ 'representations' => [ 'language' => 'en', 'representation' => 'goat' ] ]
		);

		return [
			'no lexemeId param' => [
				[ 'data' => $this->getDataParam() ],
				[ 'apierror-missingparam', 'lexemeId' ]
			],
			'no data param' => [ [ 'lexemeId' => 'L1' ], [ 'apierror-missingparam', 'data' ] ],
			'invalid lexeme ID (random string not ID)' => [
				[ 'lexemeId' => 'foo', 'data' => $this->getDataParam() ],
				'wikibase-lexeme-api-addform-lexemeid-invalid'
			],
			'invalid lexeme ID (not a lexeme ID)' => [
				[ 'lexemeId' => 'Q11', 'data' => $this->getDataParam() ],
				[ 'wikibase-lexeme-api-addform-lexemeid-not-lexeme-id', 'Q11' ],
			],
			'data not a well-formed JSON' => [
				[ 'lexemeId' => 'L1', 'data' => '{foo' ],
				'wikibase-lexeme-api-addform-data-invalid-json'
			],
			'data not an array' => [
				[ 'lexemeId' => 'L1', 'data' => json_encode( 'foo' ) ],
				'wikibase-lexeme-api-addform-data-not-array'
			],
			'no representations in data' => [
				[ 'lexemeId' => 'L1', 'data' => $noRepresentationsInDataParams ],
				'wikibase-lexeme-api-addform-data-representations-key-missing'

			],
			'no grammatical features in data' => [
				[ 'lexemeId' => 'L1', 'data' => $noGrammaticalFeaturesInDataParams ],
				'wikibase-lexeme-api-addform-data-grammatical-features-key-missing'

			],
			'representations not an array' => [
				[ 'lexemeId' => 'L1', 'data' => $this->getDataParam( [ 'representations' => 'foo' ] ) ],
				'wikibase-lexeme-api-addform-data-representations-not-array'
			],
			'grammatical features not an array' => [
				[ 'lexemeId' => 'L1', 'data' => $this->getDataParam( [ 'grammaticalFeatures' => 'Q1' ] ) ],
				'wikibase-lexeme-api-addform-data-grammatical-features-not-array'
			],
			'empty representation list in data' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'representations' => [] ] )
				],
				'wikibase-lexeme-api-addform-representations-empty'
			],
			'no representation string in data' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'representations' => [ [ 'language' => 'en' ] ] ] )
				],
				[ 'wikibase-lexeme-api-addform-representation-text-missing', 0 ]
			],
			'no representation language in data' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'representations' => [ [ 'representation' => 'foo' ] ] ] )
				],
				[ 'wikibase-lexeme-api-addform-representation-language-missing', 0 ]
			],
			'invalid item ID as grammatical feature (random string not ID)' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'grammaticalFeatures' => [ 'foo' ] ] )
				],
				[ 'wikibase-lexeme-api-addform-grammatical-feature-itemid-invalid', 'foo' ]
			],
			'invalid item ID as grammatical feature (not an item ID)' => [
				[
					'lexemeId' => 'L1',
					'data' => $this->getDataParam( [ 'grammaticalFeatures' => [ 'L2' ] ] )
				],
				[ 'wikibase-lexeme-api-addform-grammatical-feature-not-item-id', 'L2' ]
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
		$lexeme = NewLexeme::havingId( new LexemeId( 'L1' ) )->build();

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
		$lexeme = NewLexeme::havingId( new LexemeId( 'L1' ) )->build();

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
