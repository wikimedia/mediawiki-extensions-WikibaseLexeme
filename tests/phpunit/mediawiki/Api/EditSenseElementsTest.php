<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\EditSenseElements
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class EditSenseElementsTest extends WikibaseLexemeApiTestCase {

	private const DEFAULT_SENSE_ID = 'L1-S1';

	public function testRateLimitIsCheckedWhenEditing() {
		$sense = NewSense::havingId( 'S1' )->withGloss( 'en', 'furry animal' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();
		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'a furry animal' ],
				],
			] ),
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
			[ 'action' => 'wbleditsenseelements' ],
			$params
		);

		$this->doTestQueryApiException( $params, $expectedError );
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

	public function provideInvalidParams() {
		return [
			'no senseId param' => [
				[ 'data' => $this->getDataParam() ],
				[
					'key' => 'paramvalidator-missingparam',
					'params' => [ [ 'plaintext' => 'senseId' ] ],
					'code' => 'missingparam',
					'data' => []
				],
			],
			'no data param' => [
				[ 'senseId' => self::DEFAULT_SENSE_ID ],
				[
					'key' => 'paramvalidator-missingparam',
					'params' => [ [ 'plaintext' => 'data' ] ],
					'code' => 'missingparam',
					'data' => []
				],
			],
			'invalid sense ID (random string not ID)' => [
				[ 'senseId' => 'foo', 'data' => $this->getDataParam() ],
				[
					'key' => 'apierror-wikibaselexeme-parameter-not-sense-id',
					// TODO Empty path questionable result of Error reuse (w/ and w/o path)
					'params' => [ 'senseId', '', '"foo"' ],
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'senseId',
						'fieldPath' => []
					]
				]
			],
			'data not a well-formed JSON object' => [
				[ 'senseId' => self::DEFAULT_SENSE_ID, 'data' => '{foo' ],
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
			'Sense is not found' => [
				[ 'senseId' => 'L999-S1', 'data' => $this->getDataParam() ],
				[
					'key' => 'apierror-wikibaselexeme-sense-not-found',
					'params' => [ 'senseId', 'L999-S1' ],
					'code' => 'not-found',
					'data' => [
						'parameterName' => 'senseId',
						'fieldPath' => [] // TODO Is empty fields path for native params desired?
					]
				],
			],
		];
	}

	public function testGivenOtherGlosses_changesGlossesOfSense() {
		$sense = NewSense::havingId( 'S1' )->withGloss( 'en', 'furry animal' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'a furry animal' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$sense = $lexeme->getSenses()->getById( new SenseId( self::DEFAULT_SENSE_ID ) );
		$this->assertEquals( 'a furry animal', $sense->getGlosses()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenNoGlossesAfterApply_violationIsReported() {
		$sense = NewSense::havingId( 'S1' )->withGloss( 'en', 'furry animal' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'remove' => '' ],
				],
			] ),
		];

		$this->doTestQueryApiException( $params, [
			'key' => 'apierror-wikibaselexeme-sense-must-have-at-least-one-gloss',
			'code' => 'unprocessable-request',
		] );
	}

	public function testGivenGlossNotThere_glossIsRemoved() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->withGloss( 'en-x-Q123', 'hairy animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'remove' => '' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$sense = $lexeme->getSenses()->getById( new SenseId( self::DEFAULT_SENSE_ID ) );
		$this->assertEquals( 'furry animal', $sense->getGlosses()->getByLanguage( 'en' )->getText() );
		$this->assertFalse( $sense->getGlosses()->hasTermForLanguage( 'en-x-Q123' ) );
	}

	public function testGivenGlossForNewLanguage_glossIsAdded() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'furry animal' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'hairy animal' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$sense = $lexeme->getSenses()->getById( new SenseId( self::DEFAULT_SENSE_ID ) );
		$this->assertEquals( 'furry animal', $sense->getGlosses()->getByLanguage( 'en' )->getText() );
		$this->assertEquals(
			'hairy animal',
			$sense->getGlosses()->getByLanguage( 'en-x-Q123' )->getText()
		);
	}

	public function testGivenChangedGloss_summarySetAccordingly() {
		$sense = NewSense::havingId( 'S1' )->withGloss( 'en', 'furry animal' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'a furry animal' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$senseRevision = $this->getCurrentRevisionForSense( self::DEFAULT_SENSE_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$senseRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* set-sense-glosses:1|en|L1-S1 */ en: a furry animal',
			$revision->getComment()->text
		);
	}

	public function testGivenAddedGlossInNewLanguage_summarySetAccordingly() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'furry animal' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'hairy animal' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$senseRevision = $this->getCurrentRevisionForSense( self::DEFAULT_SENSE_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$senseRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* add-sense-glosses:1|en-x-Q123|L1-S1 */ en-x-Q123: hairy animal',
			$revision->getComment()->text
		);
	}

	public function testGivenAddedAndRemovedGlossInSameSense_summarySetAccordingly() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'remove' => '' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'hairy animal' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$senseRevision = $this->getCurrentRevisionForSense( self::DEFAULT_SENSE_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$senseRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* update-sense-glosses:0||L1-S1 */',
			$revision->getComment()->text
		);
	}

	public function testGivenAddedTwoGlosses_summarySetAccordingly() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'hairy animal' ],
					'en-ca' => [ 'language' => 'en-ca', 'value' => 'a furry animal' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$senseRevision = $this->getCurrentRevisionForSense( self::DEFAULT_SENSE_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$senseRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* add-sense-glosses:2||L1-S1 */ en-x-Q123: hairy animal, en-ca: a furry animal',
			$revision->getComment()->text
		);
	}

	public function testGivenRemovedGloss_summarySetAccordingly() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->withGloss( 'en-x-Q123', 'hairy animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'remove' => '' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$senseRevision = $this->getCurrentRevisionForSense( self::DEFAULT_SENSE_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$senseRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* remove-sense-glosses:1|en-x-Q123|L1-S1 */ en-x-Q123: hairy animal',
			$revision->getComment()->text
		);
	}

	public function testGivenSenseEdited_responseContainsSuccessMarker() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	public function testGivenSenseEdited_responseContainsSavedSenseData() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'furry animal' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'hairy animal' ],
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertEquals(
			[
				'id' => self::DEFAULT_SENSE_ID,
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'furry animal' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'hairy animal' ],
				],
			],
			$result['sense']
		);
	}

	public function testEditOfSenseWithoutPermission_violationIsReported() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

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
				'action' => 'wbleditsenseelements',
				'senseId' => self::DEFAULT_SENSE_ID,
				'data' => $this->getDataParam()
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
	private function getCurrentRevisionForSense( $id ) {
		$lookup = WikibaseRepo::getStore()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED );

		return $lookup->getEntityRevision( new SenseId( $id ) );
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
					->withGloss( 'fr', 'animal' )
			)
			->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();

		$params = [
			'senseId' => self::DEFAULT_SENSE_ID,
			'action' => 'wbleditsenseelements',
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'furry animal' ],
				],
			] ),
		];
		// Do the mid edit using another user to avoid wikibase ignoring edit as "self-conflict"
		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );

		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'baserevid' => $baseRevId,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
			] ),
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
			'action' => 'wbleditsenseelements',
			'senseId' => 'L1-S1',
			'baserevid' => $baseRevId,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );
		$lemmas = $lexeme->getLemmas()->toTextArray();
		$this->assertEquals( 'Hello', $lemmas['en'] );
		$senses = $lexeme->getSenses()->toArray();
		$this->assertCount( 1, $senses );
	}

	public function testAvoidSelfConflict() {
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
		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );
		$params = [
			'action' => 'wbleditsenseelements',
			'senseId' => 'L1-S1',
			'baserevid' => $baseRevId,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'cat' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );

		$lexeme = $this->getLexeme( 'L1' );
		$senses = $lexeme->getSenses()->toArray();
		$this->assertCount( 1, $senses );
		$this->assertSame(
			'cat',
			$senses[0]->getGlosses()->getByLanguage( 'en' )->getText()
		);
	}

	public function testEditSenseElementsWithTags() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->saveEntity( $lexeme );
		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbleditsenseelements',
			'senseId' => self::DEFAULT_SENSE_ID,
			'data' => json_encode( [
				'glosses' => [
					'en' => [ 'language' => 'en', 'value' => 'furry animal' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'hairy animal' ],
				],
			] ),
		] );
	}

}
