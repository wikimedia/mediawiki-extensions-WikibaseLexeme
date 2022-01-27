<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddSense
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class AddSenseTest extends WikibaseLexemeApiTestCase {

	public function testRateLimitIsCheckedWhenEditing() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbladdsense',
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
			$this->assertSame( 'actionthrottledtext', $e->getMessageObject()->getKey() );
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
			[ 'action' => 'wbladdsense' ],
			$params
		);

		$this->doTestQueryApiException( $params, $expectedError );
	}

	public function provideInvalidParams() {
		return [
			'no lexemeId param' => [
				[ 'data' => $this->getDataParam() ],
				[
					'key' => 'paramvalidator-missingparam',
					'params' => [ [ 'plaintext' => 'lexemeId' ] ],
					'code' => 'missingparam',
					'data' => []
				],
			],
			'no data param' => [
				[ 'lexemeId' => 'L1' ],
				[
					'key' => 'paramvalidator-missingparam',
					'params' => [ [ 'plaintext' => 'data' ] ],
					'code' => 'missingparam',
					'data' => []
				],
			],
			'invalid lexeme ID (random string not ID)' => [
				[ 'lexemeId' => 'foo', 'data' => $this->getDataParam() ],
				[
					'key' => 'apierror-wikibaselexeme-parameter-not-lexeme-id',
					'params' => [ 'lexemeId', '"foo"' ],
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'lexemeId',
						'fieldPath' => [] // TODO Is empty fields path for native params desired?
					]
				]
			],
			'data not a well-formed JSON object' => [
				[ 'lexemeId' => 'L1', 'data' => '{foo' ],
				[
					'key' => 'apierror-wikibaselexeme-parameter-invalid-json-object',
					'params' => [ 'data', '{foo' ],
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'data',
						'fieldPath' => [] // TODO Is empty fields path for native params desired?
					]
				],
			],
			'Lexeme is not found' => [
				[ 'lexemeId' => 'L999', 'data' => $this->getDataParam() ],
				[
					'key' => 'apierror-wikibaselexeme-lexeme-not-found',
					'params' => [ 'lexemeId', 'L999' ],
					'code' => 'not-found',
					'data' => [
						'parameterName' => 'lexemeId',
						'fieldPath' => [] // TODO Is empty fields path for native params desired?
					]
				],
			],

		];
	}

	public function testGivenNoGlossDefined_errorIsReported() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => json_encode( [ 'glosses' => [] ] )
		];

		$this->doTestQueryApiException( $params, [
			'key' => 'apierror-wikibaselexeme-sense-must-have-at-least-one-gloss',
			'code' => 'unprocessable-request',
		] );
	}

	public function testFailsOnEditConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();

		$params = [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam(),
		];

		$this->doApiRequestWithToken( $params );

		$params['baserevid'] = $baseRevId;

		$this->doTestQueryApiException( $params, [
			'params' => [ 'Edit conflict: At least two senses with the same ID were provided: `L1-S1`' ],
		] );
	}

	public function testWorksOnUnrelatedEditConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();

		$params = [
			'action' => 'wbeditentity',
			'id' => 'L1',
			'data' => '{"lemmas":{"en":{"value":"Hello","language":"en"}}}'
		];

		$this->doApiRequestWithToken( $params );

		$params = [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam(),
			'baserevid' => $baseRevId,
		];

		try {
			$this->doApiRequestWithToken( $params );
		} catch ( ApiUsageException $e ) {
			$this->assertEquals(
				'wikibase-self-conflict-patched',
				$e->getMessageObject()->getKey()
			);
		}

		$lexeme = $this->getLexeme( 'L1' );

		$lemmas = $lexeme->getLemmas()->toTextArray();
		$this->assertSame( 'Hello', $lemmas['en'] );

		$senses = $lexeme->getSenses()->toArray();

		$this->assertCount( 1, $senses );
		$this->assertSame( 'furry animal', $senses[0]->getGlosses()->getByLanguage( 'en' )->getText() );
	}

	private function getDataParam( array $dataToUse = [] ) {
		$simpleData = [
			'glosses' => [
				'en' => [
					'language' => 'en',
					'value' => 'furry animal'
				]
			],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
	}

	public function testGivenValidData_addsSense() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$senses = $lexeme->getSenses()->toArray();

		$this->assertCount( 1, $senses );
		$glossText = $senses[0]->getGlosses()->getByLanguage( 'en' )->getText();
		$this->assertSame( 'furry animal', $glossText );
	}

	public function testGivenValidData_responseContainsSuccessMarker() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	public function testGivenValidDataWithoutEditPermission_violationIsReported() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
				'*' => [
					'read' => true,
					'edit' => false
				]
		] );
		$this->resetServices();
		try {
			$this->doApiRequestWithToken( [
				'action' => 'wbladdsense',
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

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam(),
		];

		$this->doApiRequestWithToken( $params );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$lexemeRevision->getRevisionId()
		);

		$expectedComment = '/* add-sense:1|en|L1-S1 */ furry animal';
		$this->assertSame( $expectedComment, $revision->getComment()->text );
	}

	public function testResponseContainsRevisionId() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$this->assertSame( $lexemeRevision->getRevisionId(), $result['lastrevid'] );
	}

	public function testResponseContainsSenseData() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame(
			[
				'id' => 'L1-S1',
				'glosses' => [
					'en' => [
						'language' => 'en',
						'value' => 'furry animal'
					]
				],
				'claims' => [],
			],
			$result['sense']
		);
	}

	public function testAddsSenseWithTags() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$this->saveEntity( $lexeme );

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbladdsense',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam(),
		] );
	}

	/**
	 * @param string $id
	 *
	 * @return Lexeme|null
	 */
	private function getLexeme( $id ) {
		$lookup = WikibaseRepo::getEntityLookup();
		return $lookup->getEntity( new LexemeId( $id ) );
	}

	/**
	 * @param string $id
	 *
	 * @return EntityRevision|null
	 */
	private function getCurrentRevisionForLexeme( $id ) {
		$lookup = WikibaseRepo::getEntityRevisionLookup();

		return $lookup->getEntityRevision( new LexemeId( $id ) );
	}

}
