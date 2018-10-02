<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

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
use Wikibase\Store;

/**
 * @covers \Wikibase\Lexeme\Api\EditFormElements
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class EditFormElementsTest extends WikibaseLexemeApiTestCase {

	const DEFAULT_FORM_ID = 'L1-F1';

	public function testRateLimitIsCheckedWhenEditing() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();
		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goadth' ],
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

		$this->doTestQueryApiException( $params, $expectedError );
	}

	private function getDataParam( array $dataToUse = [] ) {
		$simpleData = [
			'representations' => [
				'en' => [
					'language' => 'en',
					'value' => 'colour'
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
					'key' => 'apierror-missingparam',
					'params' => [ 'formId' ],
					'code' => 'noformId',
					'data' => []
				],
			],
			'no data param' => [
				[ 'formId' => self::DEFAULT_FORM_ID ],
				[
					'key' => 'apierror-missingparam',
					'params' => [ 'data' ],
					'code' => 'nodata',
					'data' => []
				],
			],
			'invalid form ID (random string not ID)' => [
				[ 'formId' => 'foo', 'data' => $this->getDataParam() ],
				[
					'key' => 'wikibaselexeme-api-error-parameter-not-form-id',
					// TODO Empty path questionable result of Error reuse (w/ and w/o path)
					'params' => [ 'formId', '', '"foo"' ],
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'formId',
						'fieldPath' => []
					]
				]
			],
			'data not a well-formed JSON object' => [
				[ 'formId' => self::DEFAULT_FORM_ID, 'data' => '{foo' ],
				[
					'key' => 'wikibaselexeme-api-error-parameter-invalid-json-object',
					'params' => [ 'data', '{foo' ],
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'data',
						'fieldPath' => [] // TODO Is empty fields path for native params desired?
					]
				],
			],
			'Form is not found' => [
				[ 'formId' => 'L999-F1', 'data' => $this->getDataParam() ],
				[
					'key' => 'wikibaselexeme-api-error-form-not-found',
					'params' => [ 'formId', 'L999-F1' ],
					'code' => 'not-found',
					'data' => [
						'parameterName' => 'formId',
						'fieldPath' => [] // TODO Is empty fields path for native params desired?
					]
				],
			],

			'invalid item ID as grammatical feature (random string not ID)' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'grammaticalFeatures' => [ 'foo' ] ]
					)
				],
				[
					'key' => 'wikibaselexeme-api-error-json-field-not-item-id',
					'params' => [ 'data', 'grammaticalFeatures/0', '"foo"' ],
					'code' => 'bad-request', // TODO: was not-found, why?
					'data' => [
						'parameterName' => 'data',
						'fieldPath' => [ 'grammaticalFeatures', 0 ]
					]
				]
			],
			'invalid item ID as grammatical feature (not an item ID)' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'grammaticalFeatures' => [ 'L2' ] ]
					)
				] ,
				[
					'key' => 'wikibaselexeme-api-error-json-field-not-item-id',
					'params' => [ 'data', 'grammaticalFeatures/0', '"L2"' ],
					'code' => 'bad-request', // TODO: was not-found, why?
					'data' => [
						'parameterName' => 'data',
						'fieldPath' => [ 'grammaticalFeatures', 0 ]
					]
				]
			],
		];
	}

	public function testGivenOtherRepresentations_changesRepresentationsOfForm() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goadth' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( self::DEFAULT_FORM_ID ) );
		$this->assertEquals( 'goadth', $form->getRepresentations()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenNoRepresentationsAfterApply_violationIsReported() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'remove' => '' ],
				],
			] ),
		];

		$this->doTestQueryApiException( $params, [
			'key' => 'wikibaselexeme-api-error-form-must-have-at-least-one-representation',
			'code' => 'unprocessable-request',
		] );
	}

	public function testGivenRepresentationNotThere_representationIsRemoved() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->andRepresentation( 'en-x-Q123', 'color' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'remove' => '' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( self::DEFAULT_FORM_ID ) );
		$this->assertEquals( 'colour', $form->getRepresentations()->getByLanguage( 'en' )->getText() );
		$this->assertFalse( $form->getRepresentations()->hasTermForLanguage( 'en-x-Q123' ) );
	}

	public function testGivenRepresentationForNewLanguage_representationIsAdded() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'color' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( self::DEFAULT_FORM_ID ) );
		$this->assertEquals( 'colour', $form->getRepresentations()->getByLanguage( 'en' )->getText() );
		$this->assertEquals(
			'color',
			$form->getRepresentations()->getByLanguage( 'en-x-Q123' )->getText()
		);
	}

	public function testGivenOtherGrammaticalFeatures_grammaticalFeaturesAreChanged() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
				'grammaticalFeatures' => [ 'Q321' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( self::DEFAULT_FORM_ID ) );
		$this->assertEquals( [ new ItemId( 'Q321' ) ], $form->getGrammaticalFeatures() );
	}

	public function testGivenNewGrammaticalFeature_grammaticalFeatureIsAdded() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
				'grammaticalFeatures' => [ 'Q123', 'Q678' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( self::DEFAULT_FORM_ID ) );
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

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( self::DEFAULT_FORM_ID ) );
		$this->assertEmpty( $form->getGrammaticalFeatures() );
	}

	public function testGivenChangedRepresentation_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goadth' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

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

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'color' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* add-form-representations:1|en-x-Q123|L1-F1 */ en-x-Q123: color',
			$revision->getComment()->text
		);
	}

	public function testGivenAddedAndRemovedRepresentationInSameForm_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'remove' => '' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'color' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* update-form-representations:0||L1-F1 */',
			$revision->getComment()->text
		);
	}

	public function testGivenAddedTwoRepresentations_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'color' ],
					'en-ca' => [ 'language' => 'en-ca', 'value' => 'maple' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* add-form-representations:2||L1-F1 */ en-x-Q123: color, en-ca: maple',
			$revision->getComment()->text
		);
	}

	public function testGivenRemovedRepresentation_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->andRepresentation( 'en-x-Q123', 'color' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'remove' => '' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* remove-form-representations:1|en-x-Q123|L1-F1 */ en-x-Q123: color',
			$revision->getComment()->text
		);
	}

	public function testGivenAddedGrammaticalFeature_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
				'grammaticalFeatures' => [ 'Q123', 'Q678' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

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

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
				'grammaticalFeatures' => [],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertStringStartsWith(
			'/* remove-form-grammatical-features:1||L1-F1 */',
			$revision->getComment()->text
		);
		$this->assertContains( 'Q123', $revision->getComment()->text );
	}

	public function testGivenAddedAndRemovedGrammaticalFeature_summarySetAccordingly() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andGrammaticalFeature( 'Q456' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'grammaticalFeatures' => [ 'Q123', 'Q678' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertSame(
			'/* update-form-grammatical-features:0||L1-F1 */',
			$revision->getComment()->text
		);
	}

	public function testGivenSeveralPartsChanged_genericSummaryUsed() {
		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'colour' )
			->andGrammaticalFeature( 'Q123' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'color' ],
				],
				'grammaticalFeatures' => [ 'Q678' ],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

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

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
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

		$this->saveEntity( $lexeme );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'color' ],
				],
				'grammaticalFeatures' => [ 'Q321' ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertEquals(
			[
				'id' => self::DEFAULT_FORM_ID,
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'color' ],
				],
				'grammaticalFeatures' => [ 'Q321' ],
				'claims' => [],
			],
			$result['form']
		);
	}

	public function testEditOfFormWithoutPermission_violationIsReported() {
		$form = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q123' )
			->andRepresentation( 'en', 'goat' )
			->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
			'*' => [
				'read' => true,
				'edit' => false
			]
		] );

		try {
			$this->doApiRequestWithToken( [
				'action' => 'wbleditformelements',
				'formId' => self::DEFAULT_FORM_ID,
				'data' => $this->getDataParam()
			], null, self::createTestUser()->getUser() );
			$this->fail( 'Expected apierror-writeapidenied to be raised' );
		} catch ( ApiUsageException $exception ) {
			$this->assertSame( 'apierror-writeapidenied', $exception->getMessageObject()->getKey() );
		}
	}

	public function testCanAddStatementToForm() {
		$this->saveEntity( NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
			)->build() );

		$property = 'P909';
		$claim = [
			'mainsnak' => [ 'snaktype' => 'novalue', 'property' => $property ],
			'type' => 'claim',
			'rank' => 'normal',
		];

		list( $result, ) = $this->doApiRequestWithToken( [
			'action' => 'wbleditformelements',
			'formId' => 'L1-F1',
			'data' => $this->getDataParam( [
				'claims' => [ $claim ],
			] )
		] );

		$this->assertArrayHasKey( $property, $result['form']['claims'] );
		$resultClaim = $result['form']['claims'][$property][0];
		$this->assertSame( $claim['mainsnak']['snaktype'], $resultClaim['mainsnak']['snaktype'] );
		$this->assertStringStartsWith( $result['form']['id'], $resultClaim['id'] );
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
		$lookup = $this->wikibaseRepo->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED );

		return $lookup->getEntityRevision( new FormId( $id ) );
	}

}
