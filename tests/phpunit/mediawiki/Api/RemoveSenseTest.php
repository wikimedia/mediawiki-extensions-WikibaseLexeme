<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\RemoveSense
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class RemoveSenseTest extends WikibaseLexemeApiTestCase {

	public function testRateLimitIsCheckedWhenEditing() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wblremovesense',
			'id' => 'L1-S1',
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
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$this->setContentLang( 'qqq' );
		$params = array_merge(
			[ 'action' => 'wblremovesense' ],
			$params
		);

		$this->doTestQueryApiException( $params, $expectedError );
	}

	public function provideInvalidParams() {
		return [
			'no id param' => [
				[],
				[
					'key' => 'paramvalidator-missingparam',
					'params' => [ [ 'plaintext' => 'id' ] ],
					'code' => 'missingparam',
					'data' => []
				],
			],
			'invalid id (random string not ID)' => [
				[ 'id' => 'foo' ],
				[
					'key' => 'apierror-wikibaselexeme-parameter-not-sense-id',
					'params' => [ 'id', '', '"foo"' ], // TODO Empty path questionable result of reuse
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'id',
						'fieldPath' => []
					]
				]
			],
			'Lexeme is not found' => [
				[ 'id' => 'L999-S1' ],
				[
					'key' => 'apierror-wikibaselexeme-lexeme-not-found',
					'params' => [ 'id', 'L999' ],
					'code' => 'not-found',
					'data' => [
						'parameterName' => 'id',
						'fieldPath' => []
					]
				],
			],
			'Sense is not found' => [
				[ 'id' => 'L1-S4711' ],
				[
					'key' => 'apierror-wikibaselexeme-sense-not-found',
					'params' => [ 'id', 'L1-S4711' ],
					'code' => 'not-found',
					'data' => [
						'parameterName' => 'id',
						'fieldPath' => []
					]
				],
			],
		];
	}

	public function testGivenValidData_removesSense() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$this->doApiRequestWithToken( [
			'action' => 'wblremovesense',
			'id' => 'L1-S1',
		] );

		$this->assertCount( 0, $this->getLexeme( 'L1' )->getSenses() );
	}

	public function testGivenValidData_responseContainsSuccessMarker() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wblremovesense',
			'id' => 'L1-S1',
		] );

		$this->assertSame( 1, $result['success'] );
	}

	public function testSetsTheSummaryOfRevision() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$this->doApiRequestWithToken( [
			'action' => 'wblremovesense',
			'id' => 'L1-S1',
		] );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$lexemeRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* remove-sense:1|fr|L1-S1 */ goat',
			$revision->getComment()->text
		);
	}

	public function testResponseContainsRevisionId() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wblremovesense',
			'id' => 'L1-S1',
		] );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );
		$this->assertEquals( $lexemeRevision->getRevisionId(), $result['lastrevid'] );
	}

	public function testGivenValidDataWithoutEditPermission_violationIsReported() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
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
				'action' => 'wblremovesense',
				'id' => 'L1-S1',
			], null, self::createTestUser()->getUser() );
			$this->fail( 'Expected apierror-writeapidenied to be raised' );
		} catch ( ApiUsageException $exception ) {
			$this->assertSame( 'apierror-writeapidenied', $exception->getMessageObject()->getKey() );
		}
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

	public function testFailsOnEditConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();
		$params = [
			'senseId' => 'L1-S1',
			'action' => 'wbleditsenseelements',
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
			] ),
		];
		// Do the mid edit using another user to avoid wikibase ignoring edit as "self-conflict"
		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );
		$params = [
			'action' => 'wblremovesense',
			'id' => 'L1-S1',
			'baserevid' => $baseRevId
		];

		try {
			$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester2' ) );
		} catch ( ApiUsageException $e ) {
			$this->assertEquals(
				'edit-conflict',
				$e->getMessageObject()->getKey()
			);
			return;
		}

		$this->fail( 'Failed to detect the edit conflict' );
	}

	public function testWorksOnUnrelatedEditConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();
		$params = [
			'action' => 'wbeditentity',
			'id' => 'L1',
			'data' => '{"lemmas":{"en":{"value":"Hello","language":"en"}}}'
		];
		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );
		\RequestContext::getMain()->setUser( User::newSystemUser( 'Tester2' ) );
		$params = [
			'action' => 'wblremovesense',
			'id' => 'L1-S1',
			'baserevid' => $baseRevId
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );
		$lemmas = $lexeme->getLemmas()->toTextArray();
		$this->assertEquals( 'Hello', $lemmas['en'] );
		$senses = $lexeme->getSenses()->toArray();
		$this->assertCount( 0, $senses );
	}

	public function testRemovesSenseWithTags() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wblremovesense',
			'id' => 'L1-S1',
		] );
	}

}
