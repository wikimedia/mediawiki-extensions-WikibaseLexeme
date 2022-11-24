<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\EditFormElements
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class EditFormElementsTest extends WikibaseLexemeApiTestCase {

	private const DEFAULT_FORM_ID = 'L1-F1';
	private const GRAMMATICAL_FEATURE_ITEM_ID = 'Q17';

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
			'grammaticalFeatures' => [ self::GRAMMATICAL_FEATURE_ITEM_ID ],
		];

		return json_encode( array_merge( $simpleData, $dataToUse ) );
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
			'no formId param' => [
				[ 'data' => $this->getDataParam() ],
				[
					'key' => 'paramvalidator-missingparam',
					'params' => [ [ 'plaintext' => 'formId' ] ],
					'code' => 'missingparam',
					'data' => []
				],
			],
			'no data param' => [
				[ 'formId' => self::DEFAULT_FORM_ID ],
				[
					'key' => 'paramvalidator-missingparam',
					'params' => [ [ 'plaintext' => 'data' ] ],
					'code' => 'missingparam',
					'data' => []
				],
			],
			'invalid form ID (random string not ID)' => [
				[ 'formId' => 'foo', 'data' => $this->getDataParam() ],
				[
					'key' => 'apierror-wikibaselexeme-parameter-not-form-id',
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
					'key' => 'apierror-wikibaselexeme-parameter-invalid-json-object',
					'params' => [ 'data', '{foo' ],
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'data',
						'fieldPath' => [] // TODO Is empty fields path for native params desired?
					]
				],
			],
			'Form is not found' => [
				[ 'formId' => 'L999-F1', 'data' => json_encode( $basicData ) ],
				[
					'key' => 'apierror-wikibaselexeme-form-not-found',
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
					'key' => 'apierror-wikibaselexeme-json-field-not-item-id',
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
					'key' => 'apierror-wikibaselexeme-json-field-not-item-id',
					'params' => [ 'data', 'grammaticalFeatures/0', '"L2"' ],
					'code' => 'bad-request', // TODO: was not-found, why?
					'data' => [
						'parameterName' => 'data',
						'fieldPath' => [ 'grammaticalFeatures', 0 ]
					]
				]
			],
			'invalid Item ID as grammatical feature (Item ID not found)' => [
				[
					'formId' => self::DEFAULT_FORM_ID,
					'data' => $this->getDataParam(
						[ 'grammaticalFeatures' => [ 'Q2' ] ]
					)
				] ,
				[
					'key' => 'apierror-wikibaselexeme-invalid-item-id',
					'params' => [ 'data', 'grammaticalFeatures', 'Q2' ],
					'code' => 'bad-request',
					'data' => [
						'parameterName' => 'data',
						'fieldPath' => [ 'grammaticalFeatures' ]
					]
				]
			],
		];
	}

	public function testGivenOtherRepresentations_changesRepresentationsOfForm() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

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
			'key' => 'apierror-wikibaselexeme-form-must-have-at-least-one-representation',
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
				'grammaticalFeatures' => [ self::GRAMMATICAL_FEATURE_ITEM_ID ],
			] ),
		];

		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );

		$form = $lexeme->getForms()->getById( new FormId( self::DEFAULT_FORM_ID ) );
		$this->assertEquals(
			[ new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ],
			$form->getGrammaticalFeatures()
		);
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

		$this->saveEntity( new Item( new ItemId( 'Q123' ) ) );
		$this->saveEntity( new Item( new ItemId( 'Q678' ) ) );

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
		$this->assertSame( [], $form->getGrammaticalFeatures() );
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

		$this->saveEntity( new Item( new ItemId( 'Q123' ) ) );
		$this->saveEntity( new Item( new ItemId( 'Q678' ) ) );

		$this->doApiRequestWithToken( $params );

		$formRevision = $this->getCurrentRevisionForForm( self::DEFAULT_FORM_ID );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$formRevision->getRevisionId()
		);

		$this->assertStringStartsWith(
			'/* add-form-grammatical-features:1||L1-F1 */',
			$revision->getComment()->text
		);
		$this->assertStringContainsString( 'Q678', $revision->getComment()->text );
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
		$this->assertStringContainsString( 'Q123', $revision->getComment()->text );
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

		$this->saveEntity( new Item( new ItemId( 'Q123' ) ) );
		$this->saveEntity( new Item( new ItemId( 'Q678' ) ) );

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

		$this->saveEntity( new Item( new ItemId( 'Q678' ) ) );

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
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

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
				'grammaticalFeatures' => [ self::GRAMMATICAL_FEATURE_ITEM_ID ],
			] ),
		];

		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertEquals(
			[
				'id' => self::DEFAULT_FORM_ID,
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'color' ],
				],
				'grammaticalFeatures' => [ self::GRAMMATICAL_FEATURE_ITEM_ID ],
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
		$this->resetServices();

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
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

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
		$this->assertStatementGuidHasEntityId( $result['form']['id'], $resultClaim['id'] );
	}

	public function testFailsOnEditConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
				->andRepresentation( 'fr', 'foo' )
				->build()
			)
			->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();

		$params = [
			'formId' => self::DEFAULT_FORM_ID,
			'action' => 'wbleditformelements',
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
				],
			] ),
		];
		// Do the mid edit using another user to avoid wikibase ignoring edit as "self-conflict"
		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );

		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'baserevid' => $baseRevId,
			'data' => json_encode( [
				'representations' => [
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
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'foo' )
					->build()
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
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'baserevid' => $baseRevId,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params );

		$lexeme = $this->getLexeme( 'L1' );
		$lemmas = $lexeme->getLemmas()->toTextArray();
		$this->assertEquals( 'Hello', $lemmas['en'] );
		$forms = $lexeme->getForms()->toArray();
		$this->assertCount( 1, $forms );
	}

	public function testAvoidSelfConflict() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'foo' )
					->build()
			)
			->build();
		$this->saveEntity( $lexeme );
		$baseRevId = $this->getCurrentRevisionForLexeme( 'L1' )->getRevisionId();
		$params = [
			'formId' => self::DEFAULT_FORM_ID,
			'action' => 'wbleditformelements',
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'colour' ],
				],
			] ),
		];
		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );
		$params = [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'baserevid' => $baseRevId,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goat' ],
				],
			] ),
		];

		$this->doApiRequestWithToken( $params, null, User::newSystemUser( 'Tester' ) );

		$lexeme = $this->getLexeme( 'L1' );
		$forms = $lexeme->getForms()->toArray();
		$this->assertCount( 1, $forms );
		$this->assertSame(
			'goat',
			$forms[0]->getRepresentations()->getByLanguage( 'en' )->getText()
		);
	}

	public function testEditFormsWithTags() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbleditformelements',
			'formId' => self::DEFAULT_FORM_ID,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'goadth' ],
				],
				'grammaticalFeatures' => [],
			] ),
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
	private function getCurrentRevisionForForm( $id ) {
		$lookup = WikibaseRepo::getStore()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED );

		return $lookup->getEntityRevision( new FormId( $id ) );
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
