<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\RemoveForm
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class RemoveFormTest extends WikibaseLexemeApiTestCase {

	public function testRateLimitIsCheckedWhenEditing() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wblremoveform',
			'id' => 'L1-F1',
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
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

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
					'key' => 'paramvalidator-missingparam',
					'params' => [ [ 'plaintext' => 'id' ] ],
					'code' => 'missingparam',
					'data' => []
				],
			],
			'invalid id (random string not ID)' => [
				[ 'id' => 'foo' ],
				[
					'key' => 'apierror-wikibaselexeme-parameter-not-form-id',
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
					'key' => 'apierror-wikibaselexeme-lexeme-not-found',
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
					'key' => 'apierror-wikibaselexeme-form-not-found',
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
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'id' => 'L1-F1',
		] );

		$this->assertCount( 0, $this->getLexeme( 'L1' )->getForms() );
	}

	public function testGivenValidData_responseContainsSuccessMarker() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'id' => 'L1-F1',
		] );

		$this->assertSame( 1, $result['success'] );
	}

	public function testSetsTheSummaryOfRevision() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'id' => 'L1-F1',
		] );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$lexemeRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* remove-form:1||L1-F1 */ goat',
			$revision->getComment()->text
		);
	}

	public function testResponseContainsRevisionId() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wblremoveform',
			'id' => 'L1-F1',
		] );

		$lexemeRevision = $this->getCurrentRevisionForLexeme( 'L1' );
		$this->assertEquals( $lexemeRevision->getRevisionId(), $result['lastrevid'] );
	}

	public function testGivenValidDataWithoutEditPermission_violationIsReported() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
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
				'action' => 'wblremoveform',
				'id' => 'L1-F1',
			], null, self::createTestUser()->getUser() );
			$this->fail( 'Expected apierror-writeapidenied to be raised' );
		} catch ( ApiUsageException $exception ) {
			$this->assertSame( 'apierror-writeapidenied', $exception->getMessageObject()->getKey() );
		}
	}

	public function testRemovesFormWithTags() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wblremoveform',
			'id' => 'L1-F1',
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

	public function testFailsOnEditConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();
		$params = [
			'formId' => 'L1-F1',
			'action' => 'wbleditformelements',
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
			] ),
		];
		// Do the mid edit using another user to avoid wikibase ignoring edit as "self-conflict"
		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );
		\RequestContext::getMain()->setUser( User::newSystemUser( 'Tester2' ) );
		$params = [
			'action' => 'wblremoveform',
			'id' => 'L1-F1',
			'baserevid' => $baseRevId
		];

		try {
			$this->doApiRequestWithToken( $params );
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
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
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
			'action' => 'wblremoveform',
			'id' => 'L1-F1',
			'baserevid' => $baseRevId
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );
		$lemmas = $lexeme->getLemmas()->toTextArray();
		$this->assertSame( 'Hello', $lemmas['en'] );
		$forms = $lexeme->getForms()->toArray();
		$this->assertCount( 0, $forms );
	}

}
