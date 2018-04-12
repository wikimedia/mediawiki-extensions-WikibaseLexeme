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
		$this->setContentLang( 'qqq' );
		$params = array_merge(
			[ 'action' => 'wblremoveform' ],
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
			'no id param' => [
				[],
				[
					'message-key' => 'apierror-missingparam',
					'message-parameters' => [ 'id' ], // request rejected by api core, hence no []
					'api-error-code' => 'noid',
					'api-error-data' => []
				],
			],
			'invalid id (random string not ID)' => [
				[ 'id' => 'foo' ],
				[
					'message-key' => 'wikibaselexeme-api-error-parameter-not-form-id',
					'message-parameters' => [ '[id]', 'foo' ],
					'api-error-code' => 'bad-request',
					'api-error-data' => []
				]
			],
			'Lexeme is not found' => [
				[ 'id' => 'L999-F1' ],
				[
					'message-key' => 'wikibaselexeme-api-error-lexeme-not-found',
					'message-parameters' => [ 'L999' ],
					'api-error-code' => 'not-found',
					'api-error-data' => []
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
