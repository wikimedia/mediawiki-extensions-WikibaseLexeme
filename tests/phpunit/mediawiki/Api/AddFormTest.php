<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @covers \Wikibase\Lexeme\Api\AddForm
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class AddFormTest extends WikibaseLexemeApiTestCase {

	public function testRateLimitIsCheckedWhenEditing() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		$this->setTemporaryHook(
			'PingLimiter',
			function ( User &$user, $action, &$result ) {
				$this->assertSame( 'edit', $action );
				$result = true;
				return false;
			} );

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No rate limit API error was raised' );
		} catch ( ApiUsageException $e ) {
			$this->assertEquals( 'actionthrottledtext', $e->getMessageObject()->getKey() );
		}
	}

	/**
	 * @dataProvider provideInvalidParams
	 */
	public function testGivenInvalidParameter_errorIsReturned(
		array $params,
		array $expectedError
	) {
		$this->setContentLang( 'qqq' );
		$params = array_merge(
			[ 'action' => 'wbladdform' ],
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
					'message-key' => 'wikibaselexeme-api-error-parameter-not-lexeme-id',
					'message-parameters' => [ 'lexemeId', 'foo' ],
					'api-error-code' => 'bad-request',
					'api-error-data' => []
				]
			],
			'data not a well-formed JSON object' => [
				[ 'lexemeId' => 'L1', 'data' => '{foo' ],
				[
					'message-key' => 'wikibaselexeme-api-error-parameter-invalid-json-object',
					'message-parameters' => [ 'data', '{foo' ],
					'api-error-code' => 'bad-request',
					'api-error-data' => []
				],
			],
			'Lexeme is not found' => [
				[ 'lexemeId' => 'L999', 'data' => $this->getDataParam() ],
				[
					'message-key' => 'wikibaselexeme-api-error-lexeme-not-found',
					'message-parameters' => [ 'L999' ],
					'api-error-code' => 'not-found',
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
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$forms = $lexeme->getForms()->toArray();

		$this->assertCount( 1, $forms );
		$this->assertEquals( 'goat', $forms[0]->getRepresentations()->getByLanguage( 'en' )->getText() );
		$this->assertEquals( [ new ItemId( 'Q17' ) ], $forms[0]->getGrammaticalFeatures() );
	}

	public function testGivenValidData_responseContainsSuccessMarker() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	public function testGivenValidDataWithoutEditPermission_violationIsReported() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveLexeme( $lexeme );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
				'*' => [
					'read' => true,
					'edit' => false
				]
		] );

		try {
			$this->doApiRequestWithToken( [
				'action' => 'wbladdform',
				'lexemeId' => 'L1',
				'data' => $this->getDataParam()
			], null, self::createTestUser()->getUser() );
			$this->fail( 'Expected apierror-writeapidenied to be raised' );
		} catch ( ApiUsageException $exception ) {
			$this->assertSame( 'apierror-writeapidenied', $exception->getMessageObject()->getKey() );
		}
	}

	public function testSetsTheSummaryOfRevision() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		$this->doApiRequestWithToken( $params );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$lexemeRevision->getRevisionId()
		);

		$this->assertEquals( '/* add-form:1||L1-F1 */ goat', $revision->getComment()->text );
	}

	public function testResponseContainsRevisionId() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$this->assertEquals( $lexemeRevision->getRevisionId(), $result['lastrevid'] );
	}

	private function saveLexeme( Lexeme $lexeme ) {
		$this->entityStore->saveEntity( $lexeme, self::class, $this->getMock( \User::class ) );
	}

	/**
	 * @param string $id
	 *
	 * @return Lexeme|null
	 */
	private function getLexeme( $id ) {
		$lookup = $this->wikibaseRepo->getEntityLookup();
		return $lookup->getEntity( new LexemeId( $id ) );
	}

	/**
	 * @param string $id
	 *
	 * @return EntityRevision|null
	 */
	private function getCurrentRevisionForLexeme( $id ) {
		$lookup = $this->wikibaseRepo->getEntityRevisionLookup();

		return $lookup->getEntityRevision( new LexemeId( $id ) );
	}

}
