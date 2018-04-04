<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMessage;
use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @covers \Wikibase\Lexeme\Api\EditFormElements
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class EditFormElementsTest extends WikibaseLexemeApiTestCase {

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';
	}

	public function testRateLimitIsCheckedWhenEditing() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();
		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'goadth' ],
				],
				'grammaticalFeatures' => [],
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
			[ 'action' => 'wbleditformelements' ],
			$params
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API error was raised' );
		} catch ( ApiUsageException $e ) {
			/** @var ApiMessage $message */
			$message = $e->getMessageObject();

			$this->assertInstanceOf( ApiMessage::class, $message );
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

	private function getDataParam( array $dataToUse = [] ) {
		$simpleData = [
			'representations' => [
				[
					'language' => 'en',
					'representation' => 'colour'
				]
			],
			'grammaticalFeatures' => [ 'Q17' ],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
	}

	public function provideInvalidParams() {
		return [
			'no formId param' => [
				[ 'data' => $this->getDataParam() ],
				[
					'message-key' => 'apierror-missingparam',
					'message-parameters' => [ 'formId' ],
					'api-error-code' => 'noformId',
					'api-error-data' => []
				],
			],
			'no data param' => [
				[ 'formId' => 'L1-F1' ],
				[
					'message-key' => 'apierror-missingparam',
					'message-parameters' => [ 'data' ],
					'api-error-code' => 'nodata',
					'api-error-data' => []
				],
			],
			'invalid form ID (random string not ID)' => [
				[ 'formId' => 'foo', 'data' => $this->getDataParam() ],
				[
					'message-key' => 'wikibaselexeme-api-error-parameter-not-form-id',
					'message-parameters' => [ 'formId', 'foo' ],
					'api-error-code' => 'bad-request',
					'api-error-data' => []
				]
			],
			'data not a well-formed JSON object' => [
				[ 'formId' => 'L1-F1', 'data' => '{foo' ],
				[
					'message-key' => 'wikibaselexeme-api-error-parameter-invalid-json-object',
					'message-parameters' => [ 'data', '{foo' ],
					'api-error-code' => 'bad-request',
					'api-error-data' => []
				],
			],
			'Form is not found' => [
				[ 'formId' => 'L999-F1', 'data' => $this->getDataParam() ],
				[
					'message-key' => 'wikibaselexeme-api-error-form-not-found',
					'message-parameters' => [ 'L999-F1' ],
					'api-error-code' => 'not-found',
					'api-error-data' => []
				],
			],
		];
	}

	public function testGivenOtherRepresentations_changesRepresentationsOfForm() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'goadth' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( 'L1-F1' ) );
		$this->assertEquals( 'goadth', $form->getRepresentations()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenRepresentationNotThere_representationIsRemoved() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->andRepresentation( 'en-us', 'color' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'colour' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( 'L1-F1' ) );
		$this->assertEquals( 'colour', $form->getRepresentations()->getByLanguage( 'en' )->getText() );
		$this->assertFalse( $form->getRepresentations()->hasTermForLanguage( 'en-us' ) );
	}

	public function testGivenRepresentationForNewLanguage_representationIsAdded() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'colour' ],
					[ 'language' => 'en-us', 'representation' => 'color' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( 'L1-F1' ) );
		$this->assertEquals( 'colour', $form->getRepresentations()->getByLanguage( 'en' )->getText() );
		$this->assertEquals( 'color', $form->getRepresentations()->getByLanguage( 'en-us' )->getText() );
	}

	public function testGivenOtherGrammaticalFeatures_grammaticalFeaturesAreChanged() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'goat' ],
				],
				'grammaticalFeatures' => [ 'Q321' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( 'L1-F1' ) );
		$this->assertEquals( [ new ItemId( 'Q321' ) ], $form->getGrammaticalFeatures() );
	}

	public function testGivenNewGrammaticalFeature_grammaticalFeatureIsAdded() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'goat' ],
				],
				'grammaticalFeatures' => [ 'Q123', 'Q678' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( 'L1-F1' ) );
		$this->assertEquals(
			[ new ItemId( 'Q123' ), new ItemId( 'Q678' ) ],
			$form->getGrammaticalFeatures()
		);
	}

	public function testGivenNoGrammaticalFeature_grammaticalFeatureIsRemoved() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'goat' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( 'L1-F1' ) );
		$this->assertEmpty( $form->getGrammaticalFeatures() );
	}

	public function testGivenChangedRepresentation_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'goadth' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( 'L1-F1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* set-form-representations:1|en|L1-F1 */ en: goadth',
			$revision->getComment()->text
		);
	}

	public function testGivenAddedRepresentationInNewLanguage_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'colour' ],
					[ 'language' => 'en-us', 'representation' => 'color' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( 'L1-F1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* add-form-representations:1|en-us|L1-F1 */ en-us: color',
			$revision->getComment()->text
		);
	}

	public function testGivenRemovedRepresentation_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->andRepresentation( 'en-us', 'color' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'colour' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( 'L1-F1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* remove-form-representations:1|en-us|L1-F1 */ en-us: color',
			$revision->getComment()->text
		);
	}

	public function testGivenAddedGrammaticalFeature_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'goat' ],
				],
				'grammaticalFeatures' => [ 'Q123', 'Q678' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( 'L1-F1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertStringStartsWith(
			'/* add-form-grammatical-features:1||L1-F1 */',
			$revision->getComment()->text
		);
		$this->assertContains( 'Q678', $revision->getComment()->text );
	}

	public function testGivenRemovedGrammaticalFeature_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'goat' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( 'L1-F1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertStringStartsWith(
			'/* remove-form-grammatical-features:1||L1-F1 */',
			$revision->getComment()->text
		);
		$this->assertContains( 'Q123', $revision->getComment()->text );
	}

	public function testGivenSeveralPartsChanged_genericSummaryUsed() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->andGrammaticalFeature( 'Q123' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'colour' ],
					[ 'language' => 'en-us', 'representation' => 'color' ],
				],
				'grammaticalFeatures' => [ 'Q678' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( 'L1-F1' );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* update-form-elements:0||L1-F1 */',
			$revision->getComment()->text
		);
	}

	public function testGivenFormEdited_responseContainsSuccessMarker() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => $this->getDataParam()
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	public function testGivenFormEdited_responseContainsSavedFormData() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => json_encode( [
				'representations' => [
					[ 'language' => 'en', 'representation' => 'colour' ],
					[ 'language' => 'en-us', 'representation' => 'color' ],
				],
				'grammaticalFeatures' => [ 'Q321' ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertEquals(
			[
				'id' => 'L1-F1',
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
					'en-us' => [ 'language' => 'en-us', 'value' => 'color' ],
				],
				'grammaticalFeatures' => [ 'Q321' ],
			],
			$result['form']
		);
	}

	/**
	 * @expectedException ApiUsageException
	 * @expectedExceptionMessage You're not allowed to edit this wiki through the API.
	 */
	public function testEditOfFormWithoutPermission_violationIsReported() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
			'*' => [
				'read' => true,
				'edit' => false
			]
		] );

		$this->doApiRequestWithToken( [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => $this->getDataParam()
		], null, self::createTestUser()->getUser() );
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
	private function getCurrentRevisionForForm( $id ) {
		$lookup = $this->wikibaseRepo->getEntityRevisionLookup();

		return $lookup->getEntityRevision( new FormId( $id ) );
	}

}
