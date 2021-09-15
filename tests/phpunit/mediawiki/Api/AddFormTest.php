<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\AddForm
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class AddFormTest extends WikibaseLexemeApiTestCase {

	private const GRAMMATICAL_FEATURE_ITEM_ID = 'Q17';

	public function testRateLimitIsCheckedWhenEditing() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

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

		$this->doTestQueryApiException( $params, $expectedError );
	}

	public function provideInvalidParams() {
		$basicData = [
			'representations' => [
				'en' => [
					'language' => 'en',
					'value' => 'goat'
				]
			],
			'grammaticalFeatures' => [],
		];
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
				[ 'lexemeId' => 'foo', 'data' => json_encode( $basicData ) ],
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
				[ 'lexemeId' => 'L999', 'data' => json_encode( $basicData ) ],
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
			'grammatical features is not found' => [
				[ 'lexemeId' => 'L1', 'data' => $this->getDataParam() ],
				[
					'key' => 'apierror-wikibaselexeme-invalid-item-id',
					'params' => [ 'data', 'grammaticalFeatures', self::GRAMMATICAL_FEATURE_ITEM_ID ],
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'data',
						'fieldPath' => [ 'grammaticalFeatures' ]
					]
				],
			],

		];
	}

	public function testGivenNoRepresentationDefined_errorIsReported() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => json_encode( [ 'representations' => [] ] )
		];

		$this->doTestQueryApiException( $params, [
			'key' => 'apierror-wikibaselexeme-form-must-have-at-least-one-representation',
			'code' => 'unprocessable-request',
		] );
	}

	public function testFailsOnEditConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		$this->doApiRequestWithToken( $params );

		$params['baserevid'] = $baseRevId;

		$this->doTestQueryApiException( $params, [
			'params' => [ 'Edit conflict: At least two forms with the same ID were provided: `L1-F1`' ],
		] );
	}

	public function testWorksOnUnrelatedEditConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbeditentity',
			'id' => 'L1',
			'data' => '{"lemmas":{"en":{"value":"Hello","language":"en"}}}'
		];

		$this->doApiRequestWithToken( $params );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam(),
			'baserevid' => $baseRevId
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
		$this->assertEquals( 'Hello', $lemmas['en'] );

		$forms = $lexeme->getForms()->toArray();

		$this->assertCount( 1, $forms );
		$this->assertEquals( 'goat', $forms[0]->getRepresentations()->getByLanguage( 'en' )->getText() );
		$this->assertEquals(
			[ new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ],
			$forms[0]->getGrammaticalFeatures()
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
			'grammaticalFeatures' => [ self::GRAMMATICAL_FEATURE_ITEM_ID ],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
	}

	public function testGivenValidData_addsForm() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

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
		$this->assertEquals(
			[ new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ],
			$forms[0]->getGrammaticalFeatures()
		);
	}

	public function testGivenValidData_responseContainsSuccessMarker() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

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

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
				'*' => [
					'read' => true,
					'edit' => false
				]
		] );
		$this->resetServices();
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

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

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

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$this->assertEquals( $lexemeRevision->getRevisionId(), $result['lastrevid'] );
	}

	public function testResponseContainsFormData() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertEquals(
			[
				'id' => 'L1-F1',
				'representations' => [
					'en' => [
						'language' => 'en',
						'value' => 'goat'
					]
				],
				'grammaticalFeatures' => [ 'Q17' ],
				'claims' => [],
			],
			$result['form']
		);
	}

	public function testCanAddFormWithStatement() {
		$this->saveEntity( NewLexeme::havingId( 'L1' )->build() );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$property = 'P909';
		$claim = [
			'mainsnak' => [ 'snaktype' => 'novalue', 'property' => $property ],
			'type' => 'claim',
			'rank' => 'normal',
		];

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wbladdform',
			'lexemeId' => 'L1',
			'data' => $this->getDataParam( [
				'claims' => [ $claim ],
			] )
		] );

		$this->assertArrayHasKey( $property, $result['form']['claims'] );
		$resultClaim = $result['form']['claims'][$property][0];
		$this->assertSame( $claim['mainsnak']['snaktype'], $resultClaim['mainsnak']['snaktype'] );
		$this->assertStatementGuidHasEntityId( $result['form']['id'], $resultClaim['id'] );
	}

	public function testAddsFormWithTags() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbladdform',
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
