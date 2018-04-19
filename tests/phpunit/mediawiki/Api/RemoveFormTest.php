<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @covers \Wikibase\Lexeme\Api\RemoveForm
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class RemoveFormTest extends WikibaseLexemeApiTestCase {

	public function testRateLimitIsCheckedWhenEditing() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wblremoveform',
			'id' => $form->getId()->getSerialization(),
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
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		$this->setContentLang( 'qqq' );
		$params = array_merge(
			[ 'action' => 'wblremoveform' ],
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
					'key' => 'wikibaselexeme-api-error-parameter-not-form-id',
					'params' => [ 'id', '', '"foo"' ], // TODO Empty path questionable result of reuse
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'id',
						'fieldPath' => []
					]
				]
			],
			'Lexeme is not found' => [
				[ 'id' => 'L999-F1' ],
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
			'Form is not found' => [
				[ 'id' => 'L1-F4711' ],
				[
					'key' => 'wikibaselexeme-api-error-form-not-found',
					'params' => [ 'id', 'L1-F4711' ],
					'code' => 'not-found',
					'data' => [
						'parameterName' => 'id',
						'fieldPath' => []
					]
				],
			],
		];
	}

	public function testGivenValidData_removesForm() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		$this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'id' => $form->getId()->getSerialization(),
		] );

		$this->assertCount( 0, $this->getLexeme( 'L1' )->getForms() );
	}

	public function testGivenValidData_responseContainsSuccessMarker() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'id' => $form->getId()->getSerialization(),
		] );

		$this->assertSame( 1, $result['success'] );
	}

	public function testSetsTheSummaryOfRevision() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		$this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'id' => $form->getId()->getSerialization(),
		] );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$lexemeRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* remove-form:1||' . $form->getId()->getSerialization() . ' */ goat',
			$revision->getComment()->text
		);
	}

	public function testResponseContainsRevisionId() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'id' => $form->getId()->getSerialization(),
		] );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );
		$this->assertEquals( $lexemeRevision->getRevisionId(), $result['lastrevid'] );
	}

	public function testGivenValidDataWithoutEditPermission_violationIsReported() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = $lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$this->saveLexeme( $lexeme );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
			'*' => [
				'read' => true,
				'edit' => false
			]
		] );

		try {
			$this->doApiRequestWithToken( [
				'action' => 'wblremoveform',
				'id' => $form->getId()->getSerialization(),
			], null, self::createTestUser()->getUser() );
			$this->fail( 'Expected apierror-writeapidenied to be raised' );
		} catch ( ApiUsageException $exception ) {
			$this->assertSame( 'apierror-writeapidenied', $exception->getMessageObject()->getKey() );
		}
	}

	private function saveLexeme( Lexeme $lexeme ) {
		$this->entityStore->saveEntity( $lexeme, self::class, $this->getTestUser()->getUser() );
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
