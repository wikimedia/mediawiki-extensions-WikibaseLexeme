<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lib\Store\EntityRevision;

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
					'key' => 'apierror-missingparam',
					'params' => [ 'id' ],
					'code' => 'noid',
					'data' => []
				],
			],
			'invalid id (random string not ID)' => [
				[ 'id' => 'foo' ],
				[
					'key' => 'wikibaselexeme-api-error-parameter-not-sense-id',
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
					'key' => 'wikibaselexeme-api-error-lexeme-not-found',
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
					'key' => 'wikibaselexeme-api-error-sense-not-found',
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
