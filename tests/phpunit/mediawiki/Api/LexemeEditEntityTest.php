<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMessage;
use ApiUsageException;
use Exception;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\EditEntity
 *
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group WikibaseAPI
 * @group Database
 * @group medium
 */
class LexemeEditEntityTest extends WikibaseLexemeApiTestCase {

	private const EXISTING_LEXEME_ID = 'L100';
	private const EXISTING_LEXEME_LEMMA = 'apple';
	private const EXISTING_LEXEME_LEMMA_LANGUAGE = 'en';
	private const EXISTING_LEXEME_LANGUAGE_ITEM_ID = 'Q66';
	private const EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID = 'Q55';
	private const EXISTING_GRAMMATICAL_FEATURE_ITEM_ID = 'Q16';
	private const EXISTING_LEXEME_FORM_1_ID = 'F1';
	private const EXISTING_LEXEME_FORM_1_LANGUAGE = 'en';
	private const EXISTING_LEXEME_FORM_1_TEXT = 'crabapple';
	private const EXISTING_LEXEME_FORM_1_LANGUAGE2 = 'en-gb';
	private const EXISTING_LEXEME_FORM_1_TEXT2 = 'crebappla';
	private const EXISTING_LEXEME_FORM_2_ID = 'F2';
	private const EXISTING_LEXEME_FORM_2_LANGUAGE = 'en';
	private const EXISTING_LEXEME_FORM_2_TEXT = 'Malus';
	private const SPECIAL_TERM_LANGUAGE = 'mis';
	private const EXISTING_LEXEME_SENSE_ID = 'S1';
	private const EXISTING_LEXEME_SENSE_GLOSS_LANG = 'en';
	private const EXISTING_LEXEME_SENSE_GLOSS_TEXT = 'a fruit of a tree of the genus Malus';

	public function testGivenNewParameterAndValidDataAreProvided_newLexemeIsCreated() {
		$lemma = 'worm';
		$lemmaLang = 'en';
		$lexemeLang = 'Q100';
		$lexCat = 'Q200';
		$representation = 'Chinese crab';
		$representationLang = 'en';
		$claim = [
			'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P909' ],
			'type' => 'claim',
			'rank' => 'normal',
		];
		$params = [
			'action' => 'wbeditentity',
			'new' => 'lexeme',
			'data' => json_encode( [
				'lemmas' => [ $lemmaLang => [ 'language' => $lemmaLang, 'value' => $lemma ] ],
				'language' => $lexemeLang,
				'lexicalCategory' => $lexCat,
				'forms' => [
					[
						'add' => '',
						'representations' => [
							$representationLang => [ 'language' => $representationLang, 'value' => $representation ],
						],
						'claims' => [ $claim ]
					]
				]
			] ),
		];

		$this->saveDummyItemToDatabase( $lexemeLang );
		$this->saveDummyItemToDatabase( $lexCat );

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertArrayHasKey( 'id', $result['entity'] );
		$this->assertSame( 'lexeme', $result['entity']['type'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$id = $result['entity']['id'];

		$lexemeData = $this->loadEntity( $id );

		$this->assertSame(
			[ $lemmaLang => [ 'language' => $lemmaLang, 'value' => $lemma ] ],
			$lexemeData['lemmas']
		);
		$this->assertSame( $lexemeLang, $lexemeData['language'] );
		$this->assertSame( $lexCat, $lexemeData['lexicalCategory'] );
		$this->assertCount( 1, $lexemeData['forms'] );

		$form = $lexemeData['forms'][0];
		$formId = "$id-F1";
		$this->assertSame( $formId, $form['id'] );
		$this->assertSame(
			[ $representationLang => [ 'language' => $representationLang, 'value' => $representation ] ],
			$form['representations']
		);
		$this->assertSame( [], $form['grammaticalFeatures'] );
		$this->assertCount( 1, $form['claims'] );
		$this->assertHasStatement( $claim, $form );
	}

	private function getDummyLexeme( $id = self::EXISTING_LEXEME_ID ) {
		return NewLexeme::havingId( $id )
			->withLemma( self::EXISTING_LEXEME_LEMMA_LANGUAGE, self::EXISTING_LEXEME_LEMMA )
			->withLexicalCategory( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID )
			->withLanguage( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID )
			->withForm(
				NewForm::havingId( self::EXISTING_LEXEME_FORM_1_ID )
					->andLexeme( $id )
					->andRepresentation(
						self::EXISTING_LEXEME_FORM_1_LANGUAGE,
						self::EXISTING_LEXEME_FORM_1_TEXT
					)->build()
			)->withForm(
				NewForm::havingId( self::EXISTING_LEXEME_FORM_2_ID )
					->andLexeme( $id )
					->andRepresentation(
						self::EXISTING_LEXEME_FORM_2_LANGUAGE,
						self::EXISTING_LEXEME_FORM_2_TEXT
					)->build()
			)->withSense(
				NewSense::havingId( self::EXISTING_LEXEME_SENSE_ID )
					->andLexeme( $id )
					->withGloss(
						self::EXISTING_LEXEME_SENSE_GLOSS_LANG,
						self::EXISTING_LEXEME_SENSE_GLOSS_TEXT
					)
			)->build();
	}

	private function saveDummyLexemeToDatabase() {
		$this->saveEntity( $this->getDummyLexeme() );
	}

	private function saveDummyItemToDatabase( $qid ) {
		$this->saveEntity( new Item( new ItemId( $qid ) ) );
	}

	public function testGivenIdOfExistingLexemeAndLemmaData_lemmaIsChanged() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertEntityFieldsEqual(
			[
				'type' => 'lexeme',
				'id' => self::EXISTING_LEXEME_ID,
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
			],
			$result['entity']
		);
	}

	public function testGivenIdOfExistingLexemeAndNewLemmaData_lemmaIsAdded() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ 'en-gb' => [ 'language' => 'en-gb', 'value' => 'appel' ] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'lemmas' => [
					'en' => [ 'language' => 'en', 'value' => 'apple' ],
					'en-gb' => [ 'language' => 'en-gb', 'value' => 'appel' ],
				]
			],
			$result['entity']
		);
	}

	public function testGivenIdOfExistingLexemeAndLemmaWithSpecialLanguage_lemmaIsAdded() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [
					self::SPECIAL_TERM_LANGUAGE => [
						'language' => self::SPECIAL_TERM_LANGUAGE,
						'value' => 'exotic'
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	public function testGivenIdOfExistingLexemeAndNewLemmaDataWithExtendedLanguageCode_lemmaIsAdded() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ 'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'appel' ] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'lemmas' => [
					'en' => [ 'language' => 'en', 'value' => 'apple' ],
					'en-x-Q123' => [ 'language' => 'en-x-Q123', 'value' => 'appel' ],
				]
			],
			$result['entity']
		);
	}

	public function testGivenIdOfExistingLexemeAndRemoveInLemmaData_lemmaIsRemoved() {
		$this->saveDummyLexemeWithMultipleLemmaVariants();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ 'en-gb' => [ 'language' => 'en-gb', 'remove' => '' ] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'lemmas' => [
					'en' => [ 'language' => 'en', 'value' => 'apple' ],
				]
			],
			$result['entity']
		);
	}

	private function saveDummyLexemeWithMultipleLemmaVariants() {
		$lexeme = $this->getDummyLexeme();
		$lexeme->setLemmas( new TermList( [
			new Term( 'en', 'apple' ),
			new Term( 'en-gb', 'appel' )
		] ) );

		$this->entityStore->saveEntity(
			$lexeme,
			self::class,
			$this->getTestUser()->getUser()
		);
	}

	public function testGivenLemmaDataAsNumberIndexedArray_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ [ 'language' => 'en', 'value' => 'worm' ] ],
			] ),
		];

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( ApiUsageException::class, $exception );

		$message = $exception->getMessageObject();
		$this->assertEquals( 'bad-request', $message->getApiCode() );
		$this->assertEquals( 'apierror-wikibaselexeme-json-field-has-wrong-type', $message->getKey() );
		$this->assertEquals(
			[ 'parameterName' => 'lemmas', 'fieldPath' => [ 0 ] ],
			$message->getApiData()
		);
	}

	public function testGivenIdOfExistingLexemeAndLanguageItem_languageIsChanged() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'language' => 'Q333',
			] ),
		];

		$this->saveDummyItemToDatabase( 'Q333' );

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'language' => 'Q333',
			],
			$result['entity']
		);
	}

	public function testGivenIdOfExistingLexemeAndLexicalCategoryItem_lexicalCategoryIsChanged() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lexicalCategory' => 'Q333',
			] ),
		];

		$this->saveDummyItemToDatabase( 'Q333' );

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'lexicalCategory' => 'Q333',
			],
			$result['entity']
		);
	}

	public function testGivenIdOfExistingLexemeAndNewData_fieldsAreChanged() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'language' => 'Q303',
				'lexicalCategory' => 'Q606',
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
			] ),
		];

		$this->saveDummyItemToDatabase( 'Q303' );
		$this->saveDummyItemToDatabase( 'Q606' );

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'language' => 'Q303',
				'lexicalCategory' => 'Q606',
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
			],
			$result['entity']
		);
	}

	public function testGivenIdOfExistingLexemeAndStatementData_statementIsAdded() {
		$this->saveDummyLexemeToDatabase();

		$property = new Property( new NumericPropertyId( 'P909' ), null, 'test' );
		$this->entityStore->saveEntity(
			$property,
			self::class,
			$this->getTestUser()->getUser()
		);

		$claim = [
			'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P909' ],
			'type' => 'statement',
			'rank' => 'normal',
		];
		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'claims' => [ $claim ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertHasStatement( $claim, $result['entity'] );
	}

	public function testGivenClearAndExisitingLexemeIdAndLemma_lemmaDataIsChanged() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'clear' => true,
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
				'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
				'lemmas' => [ 'en-gb' => [ 'language' => 'en-gb', 'value' => 'appel' ] ],
			] ),
		];

		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID );
		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID );

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
				'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
				'lemmas' => [ 'en-gb' => [ 'language' => 'en-gb', 'value' => 'appel' ] ],
			],
			$result['entity']
		);
	}

	public function testGivenClearAndExisitingFormIdAndRepresentationsData_formDataIsChanged() {
		$this->saveDummyLexemeToDatabase();

		$formId = $this->formatFormId(
			self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
		);
		$params = [
			'action' => 'wbeditentity',
			'clear' => true,
			'id' => $formId,
			'data' => json_encode( [
				'representations' => [ 'en' => [ 'language' => 'en', 'value' => 'artichoke' ] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$firstFormData = $result['entity'];

		$this->assertEquals( $formId, $firstFormData['id'] );
		$this->assertEquals(
			[ 'en' => [ 'language' => 'en', 'value' => 'artichoke' ] ],
			$firstFormData['representations']
		);
		$this->assertSame( [], $firstFormData['grammaticalFeatures'] );
		$this->assertSame( [], $firstFormData['claims'] );
	}

	public function provideInvalidData() {
		return [
			'not string as language' => [
				[ 'language' => 100 ],
				'invalid-language'
			],
			'not item ID as language (random string)' => [
				[ 'language' => 'XXX' ],
				'invalid-item-id'
			],
			'not item ID as language (property ID)' => [
				[ 'language' => 'P123' ],
				'invalid-item-id'
			],
			'empty string as a language' => [
				[ 'language' => '' ],
				'invalid-item-id'
			],
			'null as a language' => [
				[ 'language' => null ],
				'invalid-language'
			],
			'not string as lexical category' => [
				[ 'lexicalCategory' => 100 ],
				'invalid-lexical-category'
			],
			'not item ID as lexical category (random string)' => [
				[ 'lexicalCategory' => 'XXX' ],
				'invalid-item-id'
			],
			'not item ID as lexical category (property ID)' => [
				[ 'lexicalCategory' => 'P123' ],
				'invalid-item-id'
			],
			'empty string as a lexical category' => [
				[ 'lexicalCategory' => '' ],
				'invalid-item-id'
			],
			'null as a lexical category' => [
				[ 'lexicalCategory' => null ],
				'invalid-lexical-category'
			],
			'lemmas not an array' => [
				[ 'lemmas' => 'BAD' ],
				'not-recognized-array'
			],
			'no language in lemma change request' => [
				[ 'lemmas' => [ 'en' => [ 'value' => 'foo' ] ] ],
				'bad-request'
			],
			'no language in lemma change request (remove)' => [
				[ 'lemmas' => [ 'en' => [ 'remove' => '' ] ] ],
				'bad-request'
			],
			'inconsistent language in lemma change request' => [
				[ 'lemmas' => [ 'en' => [ 'language' => 'en-gb', 'value' => 'colour' ] ] ],
				'inconsistent-language'
			],
			'unknown language in lemma change request' => [
				[ 'lemmas' => [ 'SUPERODD' => [ 'language' => 'SUPERODD', 'value' => 'foo' ] ] ],
				'not-recognized-language'
			],
			'too long term in lemma change request' => [
				[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => str_repeat( 'x', 10000 ) ] ] ],
				'modification-failed'
			],
		];
	}

	/**
	 * @dataProvider provideInvalidData
	 */
	public function testGivenInvalidData_errorIsReported( array $dataArgs, $expectedErrorCode ) {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( $dataArgs ),
		];

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( ApiUsageException::class, $exception );
		/** @var ApiUsageException $exception */
		$this->assertSame( $expectedErrorCode,
			$exception->getStatusValue()->getErrors()[0]['message']->getApiCode()
		);
	}

	public function provideInvalidLexemeDataWithClear() {
		return [
			'language missing in new data' => [
				[
					'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
					'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
				],
			],
			'lexical category missing in new data' => [
				[
					'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
					'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
				],
			],
			'language and lexical category missing in new data' => [
				[
					'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
				],
			],
			'lemmas missing in new data' => [
				[
					'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
					'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
				]
			],
			'empty lemmas in new data' => [
				[
					'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
					'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
					'lemmas' => [],
				]
			],
		];
	}

	/**
	 * @dataProvider provideInvalidLexemeDataWithClear
	 */
	public function testGivenInvalidDataInClearRequest_errorIsReported( array $dataArgs ) {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'clear' => true,
			'data' => json_encode( $dataArgs ),
		];

		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID );
		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID );

		// TODO disabling exception logging probably shouldn’t be necessary (T255566)
		$this->setNullLogger( 'exception' );

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( ApiUsageException::class, $exception );
		/** @var ApiUsageException $exception */
		$this->assertSame(
			'failed-save',
			$exception->getStatusValue()->getErrors()[0]['message']->getApiCode()
		);
	}

	public function testFormReferencedAfterItWasCleared_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'clear' => true,
			'data' => json_encode( [
				'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
				'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						)
					]
				]
			] ),
		];

		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID );
		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID );

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API exception thrown when expected' );
		} catch ( ApiUsageException $e ) {
			$message = $e->getMessageObject();
			$this->assertInstanceOf( ApiMessage::class, $message );
			$this->assertEquals(
				'modification-failed',
				$message->getApiCode(),
				'API code does not match expectation'
			);
			$this->assertEquals(
				'wikibase-validator-form-not-found',
				$message->getKey(),
				'Message key does not match expectation'
			);
		}
	}

	public function provideIncompleteFormDataToGoWithClear() {
		return [
			'empty form representations in new data' => [
				[
					'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
					'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
					'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
					'forms' => [
						[
							'add' => '',
							'representations' => [],
						]
					]
				]
			],
			'no form representations in new data' => [
				[
					'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
					'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
					'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ],
					'forms' => [
						[
							'add' => '',
						]
					]
				]
			],
		];
	}

	/**
	 * @dataProvider provideIncompleteFormDataToGoWithClear
	 */
	public function testGivenClearAndInsufficientFormData_errorIsReported( array $dataArgs ) {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'clear' => true,
			'data' => json_encode( $dataArgs ),
		];

		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID );
		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID );

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API exception thrown when expected' );
		} catch ( ApiUsageException $e ) {
			$message = $e->getMessageObject();
			$this->assertInstanceOf( ApiMessage::class, $message );
			$this->assertEquals(
				'apierror-wikibaselexeme-form-must-have-at-least-one-representation',
				$message->getKey(),
				'Wrong message codes'
			);
		}
	}

	public function provideNoChangeLexemeData() {
		return [
			'empty form list ' => [ [
				'forms' => [
				]
			] ],
			'only ID of existing form' => [ [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						)
					]
				]
			] ],
		];
	}

	/**
	 * @dataProvider provideNoChangeLexemeData
	 */
	public function testGivenNoOpRequest_noEditIsMadeAndNochangeFlagSet( array $dataArgs ) {
		$this->saveDummyLexemeToDatabase();

		$lookup = WikibaseRepo::getStore()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED );

		$lexemeId = new LexemeId( self::EXISTING_LEXEME_ID );
		$revisionBeforeRequest = $lookup->getEntityRevision( $lexemeId );

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( $dataArgs )
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$revisionAfterRequest = $lookup->getEntityRevision( $lexemeId );

		$this->assertSame( 1, $result['success'] );
		$this->assertEquals(
			$revisionBeforeRequest->getRevisionId(),
			$revisionAfterRequest->getRevisionId()
		);
		$this->assertTrue( $result['entity']['nochange'] );
	}

	public function testGivenTryingToRemoveAllLemmas_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ],
			] ),
		];

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( ApiUsageException::class, $exception );
		/** @var ApiUsageException $exception */
		$this->assertSame( 'failed-save',
			$exception->getStatusValue()->getErrors()[0]['message']->getApiCode()
		);
	}

	public function testGivenTryingToCreateLexemeWithNoLemmas_errorIsReported() {
		$params = [
			'action' => 'wbeditentity',
			'new' => 'lexeme',
			'data' => json_encode( [
				'lemmas' => [],
				'language' => 'Q100',
				'lexicalCategory' => 'Q200',
			] ),
		];

		$this->saveDummyItemToDatabase( 'Q100' );
		$this->saveDummyItemToDatabase( 'Q200' );

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( ApiUsageException::class, $exception );
		/** @var ApiUsageException $exception */
		$this->assertSame( 'failed-save',
			$exception->getStatusValue()->getErrors()[0]['message']->getApiCode()
		);
	}

	public function testGivenTryingToCreateLexemeWithNonExistingLanguage_errorIsReported() {
		$params = [
			'action' => 'wbeditentity',
			'new' => 'lexeme',
			'data' => json_encode( [
				'lemmas' => [],
				'language' => 'Q100',
				'lexicalCategory' => 'Q200',
			] ),
		];

		$this->saveDummyItemToDatabase( 'Q200' );

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( ApiUsageException::class, $exception );
		/** @var ApiUsageException $exception */
		$this->assertSame( 'modification-failed',
			$exception->getStatusValue()->getErrors()[0]['message']->getApiCode()
		);
		$this->assertSame( 'wikibase-validator-no-such-entity',
			$exception->getStatusValue()->getErrors()[0]['message']->getApiData()['messages'][0]['name']
		);
	}

	public function testGivenTryingToCreateLexemeWithNonExistingLexicalCategory_errorIsReported() {
		$params = [
			'action' => 'wbeditentity',
			'new' => 'lexeme',
			'data' => json_encode( [
				'lemmas' => [],
				'language' => 'Q100',
				'lexicalCategory' => 'Q200',
			] ),
		];

		$this->saveDummyItemToDatabase( 'Q100' );

		$exception = null;
		try {
			$this->doApiRequestWithToken( $params );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( ApiUsageException::class, $exception );
		/** @var ApiUsageException $exception */
		$this->assertSame( 'modification-failed',
			$exception->getStatusValue()->getErrors()[0]['message']->getApiCode()
		);
		$this->assertSame( 'wikibase-validator-no-such-entity',
			$exception->getStatusValue()->getErrors()[0]['message']->getApiData()['messages'][0]['name']
		);
	}

	private function assertEntityFieldsEqual( array $expected, array $actual ) {
		foreach ( array_keys( $expected ) as $field ) {
			$this->assertArrayHasKey( $field, $actual );
			$this->assertSame( $expected[$field], $actual[$field] );
		}
	}

	public function testGivenIdOfExistingLexemeAndRemoveInFormData_formIsRemoved() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'remove' => '',
						'unrelatedkey' => 'no harm done'
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 1, $result['entity']['forms'] );
		$this->assertSame(
			$this->formatFormId(
				self::EXISTING_LEXEME_ID,
				self::EXISTING_LEXEME_FORM_2_ID
			),
			$result['entity']['forms'][0]['id']
		);
	}

	public function testGivenExistingLexemeAndFormRemoveAmongstOtherData_formIsRemoved() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
				'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'remove' => ''
					]
				],
			] ),
		];

		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID );
		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID );

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	public function testGivenIdOfExistingLexemeAndRemoveInAllFormData_allFormsAreRemoved() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'remove' => ''
					],
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
						),
						'remove' => ''
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 0, $result['entity']['forms'] );
	}

	public function testGivenIdOfExistingLexemeAndFirstFormRemovalFails_noneOfTheFormsAreRemoved() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, 'malformed'
						),
						'remove' => ''
					],
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
						),
						'remove' => ''
					]
				],
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'bad-request',
				'key' => 'apierror-wikibaselexeme-parameter-not-form-id',
				'params' => [ 'data', 'forms/0/id', '"L100-malformed"' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'forms', 0, 'id' ]
				]
			]
		);

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertCount( 2, $lexemeData['forms'] );
	}

	public function testGivenIdOfExistingLexemeAndRemoveUnidentifiedForm_errorReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'remove' => ''
					]
				]
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'bad-request',
				'key' => 'apierror-wikibaselexeme-json-field-required',
				'params' => [ 'data', 'forms/0', 'id' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'forms', 0 ]
				]
			]
		);
	}

	public function testGivenIdOfExistingLexemeAndRemoveBadFormId_errorReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, 'bad'
						),
						'remove' => ''
					]
				]
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'bad-request',
				'key' => 'apierror-wikibaselexeme-parameter-not-form-id',
				'params' => [ 'data', 'forms/0/id', '"L100-bad"' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'forms', 0, 'id' ]
				]
			]
		);
	}

	public function testGivenInvalidGrammaticalFeature_errorReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'grammaticalFeatures' => [ 'BAD' ]
					]
				]
			] ),
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'No API error was raised' );
		} catch ( ApiUsageException $e ) {
			/** @var ApiMessage $message */
			$message = $e->getMessageObject();

			$this->assertInstanceOf( ApiMessage::class, $message );
			$this->assertEquals(
				'apierror-wikibaselexeme-json-field-not-item-id',
				$message->getKey(),
				'Wrong message codes'
			);
			$this->assertEquals(
				[ 'data', 'forms/0/grammaticalFeatures/0', '"BAD"' ],
				$message->getParams(),
				'Wrong message parameters'
			);
			$this->assertEquals(
				'bad-request', // TODO: was "wikibaselexeme-api-error-json-field-not-item-id". Which is right?
				$message->getApiCode(),
				'Wrong api code'
			);
			$this->assertEquals(
				[
					'parameterName' => 'data',
					'fieldPath' => [ 'forms', 0, 'grammaticalFeatures', 0 ]
				],
				$message->getApiData(),
				'Wrong api data'
			);
		}
	}

	public function testGivenIdOfExistingLexemeAndOffTypeFormId_errorReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => [ 'justevil' ],
						'remove' => ''
					]
				]
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'bad-request',
				'key' => 'apierror-wikibaselexeme-parameter-not-form-id',
				'params' => [ 'data', 'forms/0/id', '["justevil"]' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'forms', 0, 'id' ]
				]
			]
		);
	}

	public function testGivenIdOfMissingLexemeAndExistingButUnrelatedFormId_errorReportedFormIntact() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => 'L777',
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'remove' => ''
					]
				]
			] ),
		];

		$this->doTestQueryExceptions( $params, [
			'code' => 'no-such-entity'
		] );

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertCount( 2, $lexemeData['forms'] );
	}

	public function testGivenMismatchOfLexemeAndFormId_errorReportedAndFormIntact() {
		$this->saveDummyLexemeToDatabase();

		$secondLexemeId = 'L33';

		$secondLexeme = $this->getDummyLexeme( $secondLexemeId );
		$secondLexeme->getForms()->remove( new FormId( $this->formatFormId(
			$secondLexemeId, self::EXISTING_LEXEME_FORM_1_ID
		) ) );

		$this->entityStore->saveEntity(
			$secondLexeme,
			self::class,
			$this->getTestUser()->getUser()
		);

		$params = [
			'action' => 'wbeditentity',
			'id' => $secondLexemeId,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'remove' => ''
					]
				]
			] ),
		];

		$this->doTestQueryExceptions( $params, [
			'code' => 'modification-failed',
			// FIXME Wikibase\Repo\Validators\ValidatorErrorLocalizer needs to become configurable
			'message-key' => 'wikibase-validator-form-not-found'
		] );

		$firstLexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );
		$secondLexemeData = $this->loadEntity( $secondLexemeId );

		$this->assertCount( 2, $firstLexemeData['forms'] );
		$this->assertCount( 1, $secondLexemeData['forms'] );
	}

	public function testGivenIdOfExistingLexemeAndRemoveOfUnknownForm_errorReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, 'F77'
						),
						'remove' => ''
					]
				]
			] ),
		];

		$this->doTestQueryExceptions( $params, [
			'code' => 'modification-failed',
			// FIXME Wikibase\Repo\Validators\ValidatorErrorLocalizer needs to become configurable
			'message-key' => 'wikibase-validator-form-not-found'
		] );
	}

	/**
	 * @dataProvider provideDataRequiringEditPermissions
	 */
	public function testEditOfLexemeWithoutEditPermission_violationIsReported( array $editData ) {
		$this->saveDummyLexemeToDatabase();

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
			'*' => [
				'read' => true,
				'edit' => false
			]
		] );
		$this->resetServices();

		try {
			$this->doApiRequestWithToken( [
				'action' => 'wbeditentity',
				'id' => self::EXISTING_LEXEME_ID,
				'data' => json_encode( $editData ),
			], null, self::createTestUser()->getUser() );
			$this->fail( 'Expected apierror-writeapidenied to be raised' );
		} catch ( ApiUsageException $exception ) {
			$this->assertSame( 'apierror-writeapidenied', $exception->getMessageObject()->getKey() );
		}
	}

	public function testGivenClearRequest_formIdCounterIsNotReset() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'clear' => true,
			'data' => json_encode( [
				'lemmas' => [ self::EXISTING_LEXEME_LEMMA_LANGUAGE => [
					'language' => self::EXISTING_LEXEME_LEMMA_LANGUAGE,
					'value' => self::EXISTING_LEXEME_LEMMA,
				] ],
				'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
				'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
			] ),
		];

		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID );
		$this->saveDummyItemToDatabase( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID );

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );

		/** @var Lexeme $lexeme */
		$lexeme = $this->getEntityLookup()->getEntity( new LexemeId( self::EXISTING_LEXEME_ID ) );
		$this->assertSame( [], $lexeme->getForms()->toArray() );
		$this->assertEquals( 3, $lexeme->getNextFormId() );
	}

	public function provideDataRequiringEditPermissions() {
		yield [
			[
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, 'F77'
						),
						'remove' => ''
					]
				]
			]
		];
		yield [
			[ 'lexicalCategory' => 'Q333' ]
		];
		yield [
			[ 'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ] ]
		];
		yield [
			[ 'language' => 'Q303' ]
		];
	}

	public function testGivenExistingLexemeAndChangeInFormRepresentations_formPropertyIsUpdated() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
						),
						'representations' => [
							'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
						]
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 2, $result['entity']['forms'] );
		$form = $result['entity']['forms'][1];
		$this->assertCount( 1, $form['representations'] );
		$this->assertSame( 'en', $form['representations']['en']['language'] );
		$this->assertSame( 'Chinese crab', $form['representations']['en']['value'] );
	}

	public function testGivenExistingLexemeAndRemovingFormRepresentations_formIsUpdatedCorrectly() {
		$this->saveLexemeWithTwoRepresentations();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'representations' => [
							self::EXISTING_LEXEME_FORM_1_LANGUAGE => [
								'language' => self::EXISTING_LEXEME_FORM_1_LANGUAGE,
								'remove' => ''
							]
						]
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 1, $result['entity']['forms'] );
		$this->assertSame(
			[
				self::EXISTING_LEXEME_FORM_1_LANGUAGE2 => [
					'language' => self::EXISTING_LEXEME_FORM_1_LANGUAGE2,
					'value' => self::EXISTING_LEXEME_FORM_1_TEXT2
				]
			],
			$result['entity']['forms'][0]['representations']
		);
	}

	public function testGivenExistingFormAndRemovingRepresentations_formIsUpdatedCorrectly() {
		$this->saveLexemeWithTwoRepresentations();

		$params = [
			'action' => 'wbeditentity',
			'id' => $this->formatFormId(
				self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
			),
			'data' => json_encode( [
				'representations' => [
					self::EXISTING_LEXEME_FORM_1_LANGUAGE => [
						'language' => self::EXISTING_LEXEME_FORM_1_LANGUAGE,
						'remove' => ''
					]
				]
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame(
			[
				self::EXISTING_LEXEME_FORM_1_LANGUAGE2 => [
					'language' => self::EXISTING_LEXEME_FORM_1_LANGUAGE2,
					'value' => self::EXISTING_LEXEME_FORM_1_TEXT2
				]
			],
			$result['entity']['representations']
		);
	}

	public function testGivenExistingLexemeAndAddingDamagedRepresentation_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
						),
						'representations' => [
							'la' => [ 'language' => 'la' ],
						]
					]
				],
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'bad-request',
				'key' => 'apierror-wikibaselexeme-json-field-required',
				'params' => [ 'data', 'forms/0/representations/la', 'value' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'forms', 0, 'representations', 'la' ]
				]
			]
		);

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertSame( self::EXISTING_LEXEME_ID, $lexemeData['id'] );
		$this->assertCount( 2, $lexemeData['forms'] );
	}

	public function testGivenExistingFormAndAddingDamagedRepresentation_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => $this->formatFormId(
				self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
			),
			'data' => json_encode( [
				'representations' => [
					'la' => [ 'language' => 'la' ],
				]
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'bad-request',
				'key' => 'apierror-wikibaselexeme-json-field-required',
				'params' => [ 'data', 'representations/la', 'value' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'representations', 'la' ]
				]
			]
		);

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertSame( self::EXISTING_LEXEME_ID, $lexemeData['id'] );
		$this->assertCount( 2, $lexemeData['forms'] );
	}

	public function testGivenExistingLexemeAddingFormWithInconsistentRepresentation_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'add' => '',
						'representations' => [
							'la' => [ 'language' => 'ay', 'value' => 'papa' ],
						]
					]
				],
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'inconsistent-language',
				'key' => 'apierror-wikibaselexeme-language-inconsistent',
				'params' => [ 'data', 'forms/0/representations/la', 'la', 'ay' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'forms', 0, 'representations', 'la' ]
				]
			]
		);

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertSame( self::EXISTING_LEXEME_ID, $lexemeData['id'] );
		$this->assertCount( 2, $lexemeData['forms'] );
	}

	public function testGivenExistingLexemeAndAddingFormRepresentation_formIsUpdatedCorrectly() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
						),
						'representations' => [
							'la' => [ 'language' => 'la', 'value' => 'Malus baccata' ],
						]
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 2, $result['entity']['forms'] );
		$form = $result['entity']['forms'][1];
		$this->assertCount( 2, $form['representations'] );
		$this->assertSame( 'Malus', $form['representations']['en']['value'] );
		$this->assertSame( 'Malus baccata', $form['representations']['la']['value'] );
	}

	public function testGivenFormToBeChangedDoesNotExistOnLexeme_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, 'F100'
						),
						'representations' => [
							'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
						]
					]
				],
			] ),
		];

		$this->doTestQueryExceptions( $params, [
			'code' => 'modification-failed',
			// FIXME Wikibase\Repo\Validators\ValidatorErrorLocalizer needs to become configurable
			'message-key' => 'wikibase-validator-form-not-found'
		] );
	}

	public function testGivenExistingLexemeAndChangeInFormFeatures_formPropertyIsUpdated() {
		$this->saveDummyLexemeToDatabase();
		$this->saveEntity( new Item( new ItemId( self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$property = 'P909';
		$snakType = 'novalue';
		$formId = $this->formatFormId(
			self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
		);
		$claim = [
			'mainsnak' => [ 'snaktype' => $snakType, 'property' => $property ],
			'type' => 'statement',
			'rank' => 'normal',
		];
		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $formId,
						'grammaticalFeatures' => [ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
						'claims' => [ $claim ],
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 2, $result['entity']['forms'] );
		$this->assertCount( 1, $result['entity']['forms'][0]['grammaticalFeatures'] );
		$this->assertSame(
			self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID,
			$result['entity']['forms'][0]['grammaticalFeatures'][0]
		);

		$this->assertHasStatement( $claim, $result['entity']['forms'][0] );
	}

	public function testGivenNewStatementOnExistingForm_statementIsAdded() {
		$this->saveDummyLexemeToDatabase();

		$formId = $this->formatFormId(
			self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
		);
		$claim = [
			'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P666' ],
			'type' => 'statement',
			'rank' => 'normal',
		];
		$params = [
			'action' => 'wbeditentity',
			'id' => $formId,
			'data' => json_encode( [
				'claims' => [ $claim ],
			] ),
		];

		[ $result ] = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( $formId, $result['entity']['id'] );
		$this->assertHasStatement( $claim, $result['entity'] );
	}

	public function testGivenNewStatementOnExistingSense_statementIsAdded() {
		$this->saveDummyLexemeToDatabase();

		$senseId = self::EXISTING_LEXEME_ID . '-' . self::EXISTING_LEXEME_SENSE_ID;
		$claim = [
			'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P666' ],
			'type' => 'statement',
			'rank' => 'normal',
		];
		$params = [
			'action' => 'wbeditentity',
			'id' => $senseId,
			'data' => json_encode( [
				'claims' => [ $claim ],
			] ),
		];

		[ $result ] = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( $senseId, $result['entity']['id'] );
		$this->assertHasStatement( $claim, $result['entity'] );
	}

	public function testGivenNewSenseWithStatement_senseWithStatementIsAdded() {
		$this->saveDummyLexemeToDatabase();
		$claim = [
			'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P666' ],
			'type' => 'statement',
			'rank' => 'normal',
		];

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'senses' => [ [
					'add' => '',
					'glosses' => [ 'en' => [ 'language' => 'en', 'value' => 'whatevs' ] ],
					'claims' => [ $claim ],
				] ],
			] ),
		] );

		$this->assertSame( 1, $result['success'] );
		$this->assertCount( 2, $result['entity']['senses'] );
		$this->assertHasStatement( $claim, $result['entity']['senses'][1] );
	}

	// TODO: edit statements (all options: add, edit, remove?) with id=L1

	public function testGivenExistingLexemeAndFormChangeAndAdd_formsAreProperlyUpdatedAndAdded() {
		$this->saveDummyLexemeToDatabase();
		$this->saveEntity( new Item( new ItemId( self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ) ) );
		$this->saveEntity( new Item( new ItemId( 'Q18' ) ) );
		$this->saveEntity( new Item( new ItemId( 'Q19' ) ) );

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'grammaticalFeatures' => [ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
					],
					[
						'add' => '',
						'representations' => [
							'la' => [ 'language' => 'la', 'value' => 'Malus baccata' ],
						],
						'grammaticalFeatures' => [ 'Q18', 'Q19' ],
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 3, $result['entity']['forms'] );

		$this->assertSame(
			[ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
			$result['entity']['forms'][0]['grammaticalFeatures']
		);

		$this->assertSame(
			$this->formatFormId(
				self::EXISTING_LEXEME_ID, 'F3'
			),
			$result['entity']['forms'][2]['id']
		);
		$this->assertSame( [ 'Q18', 'Q19' ], $result['entity']['forms'][2]['grammaticalFeatures'] );
		$this->assertCount( 1, $result['entity']['forms'][2]['representations'] );
		$this->assertSame( 'la', $result['entity']['forms'][2]['representations']['la']['language'] );
		$this->assertSame( 'Malus baccata', $result['entity']['forms'][2]['representations']['la']['value'] );
	}

	public function testGivenExistingLexemeAndFormDataWithAddKey_formIsAdded() {
		$this->saveDummyLexemeToDatabase();
		$this->saveEntity( new Item( new ItemId( self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'add' => '',
						'representations' => [
							'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
						],
						'grammaticalFeatures' => [ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 3, $result['entity']['forms'] );
		$this->assertEquals(
			[
				'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
			],
			$result['entity']['forms'][2]['representations']
		);
		$this->assertEquals(
			[ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
			$result['entity']['forms'][2]['grammaticalFeatures']
		);

		/** @var Lexeme $lexeme */
		$lexeme = $this->getEntityLookup()->getEntity( new LexemeId( self::EXISTING_LEXEME_ID ) );
		$this->assertSame( 4, $lexeme->getNextFormId() );
	}

	public function testGivenExistingLexemeAndTwoFormChangeOps_formsAreProperlyUpdated() {
		$this->saveDummyLexemeToDatabase();
		$this->saveEntity( new Item( new ItemId( self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ) ) );
		$this->saveEntity( new Item( new ItemId( 'Q18' ) ) );
		$this->saveEntity( new Item( new ItemId( 'Q19' ) ) );

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'grammaticalFeatures' => [ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
					],
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
						),
						'representations' => [
							'la' => [ 'language' => 'la', 'value' => 'Malus baccata' ],
						],
						'grammaticalFeatures' => [ 'Q18', 'Q19' ],
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 2, $result['entity']['forms'] );

		$this->assertSame(
			[ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
			$result['entity']['forms'][0]['grammaticalFeatures']
		);

		$form = $result['entity']['forms'][1];
		$this->assertSame( [ 'Q18', 'Q19' ], $form['grammaticalFeatures'] );

		$this->assertCount( 2, $form['representations'] );
		$this->assertSame( 'la', $form['representations']['la']['language'] );
		$this->assertSame( 'Malus baccata', $form['representations']['la']['value'] );
		$this->assertSame(
			self::EXISTING_LEXEME_FORM_2_LANGUAGE,
			$form['representations'][self::EXISTING_LEXEME_FORM_2_LANGUAGE]['language']
		);
		$this->assertSame(
			self::EXISTING_LEXEME_FORM_2_TEXT,
			$form['representations'][self::EXISTING_LEXEME_FORM_2_LANGUAGE]['value']
		);
	}

	public function testGivenNewFormAndExistingLexemeId_formIsAddedToLexeme() {
		$this->saveDummyLexemeToDatabase();
		$this->saveEntity( new Item( new ItemId( self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbeditentity',
			'new' => 'form',
			'data' => json_encode( [
				'lexemeId' => self::EXISTING_LEXEME_ID,
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
				],
				'grammaticalFeatures' => [ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
				'claims' => [ [
					'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P909' ],
					'type' => 'statement',
					'rank' => 'normal',
				] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$expectedId = $this->formatFormId( self::EXISTING_LEXEME_ID, 'F3' );
		$this->assertSame( $expectedId, $result['entity']['id'] );
		$this->assertEquals(
			[
				'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
			],
			$result['entity']['representations']
		);
		$this->assertEquals(
			[ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
			$result['entity']['grammaticalFeatures']
		);

		/** @var Lexeme $lexeme */
		$lexeme = $this->getEntityLookup()->getEntity( new LexemeId( self::EXISTING_LEXEME_ID ) );
		$this->assertSame( 4, $lexeme->getNextFormId() );
	}

	public function testGivenNewFormAndExistingLexemeId_newFormIdIsReturned() {
		$this->saveDummyLexemeToDatabase();
		$this->saveEntity( new Item( new ItemId( self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbeditentity',
			'new' => 'form',
			'data' => json_encode( [
				'lexemeId' => self::EXISTING_LEXEME_ID,
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
				],
				'grammaticalFeatures' => [ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
				'claims' => [],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( self::EXISTING_LEXEME_ID . '-F3', $result['entity']['id'] );
	}

	public function testGivenNewFormAndInvalidLexemeId_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'new' => 'form',
			'data' => json_encode( [
				'lexemeId' => 'foo',
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
				],
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'bad-request',
				'key' => 'apierror-wikibaselexeme-parameter-not-lexeme-id',
				'params' => [ 'data', '"foo"' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'lexemeId' ]
				]
			]
		);
	}

	public function testGivenNewFormAndNonLexemeId_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'new' => 'form',
			'data' => json_encode( [
				'lexemeId' => 'Q2',
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
				],
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'bad-request',
				'key' => 'apierror-wikibaselexeme-parameter-not-lexeme-id',
				'params' => [ 'data', '"Q2"' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'lexemeId' ]
				]
			]
		);
	}

	public function testGivenNewFormAndNonExistingLexemeId_errorIsReported() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'new' => 'form',
			'data' => json_encode( [
				'lexemeId' => 'L30000',
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
				],
			] ),
		];

		$this->doTestQueryApiException(
			$params,
			[
				'code' => 'not-found',
				'key' => 'apierror-wikibaselexeme-lexeme-not-found',
				'params' => [ 'data', 'L30000' ],
				'data' => [
					'parameterName' => 'data',
					'fieldPath' => [ 'lexemeId' ]
				]
			]
		);
	}

	public function testGivenExistingLexemeAndFormWithRemoveKey_formIsRemoved() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'remove' => '',
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertCount( 1, $result['entity']['forms'] );
		$this->assertSame(
			$this->formatFormId( self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID ),
			$result['entity']['forms'][0]['id']
		);
	}

	public function testGivenExistingFormAndChangeInFormRepresentations_formPropertyIsUpdated() {
		$this->saveDummyLexemeToDatabase();

		$formId = $this->formatFormId(
			self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
		);
		$params = [
			'action' => 'wbeditentity',
			'id' => $formId,
			'data' => json_encode( [
				'representations' => [
					'en' => [ 'language' => 'en', 'value' => 'Chinese crab' ],
				]
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( $formId, $result['entity']['id'] );
		$form = $result['entity'];
		$this->assertCount( 1, $form['representations'] );
		$this->assertSame( 'en', $form['representations']['en']['language'] );
		$this->assertSame( 'Chinese crab', $form['representations']['en']['value'] );
	}

	public function testGivenExistingFormAndAddingFormRepresentation_formPropertyIsUpdated() {
		$this->saveDummyLexemeToDatabase();

		$formId = $this->formatFormId(
			self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
		);
		$params = [
			'action' => 'wbeditentity',
			'id' => $formId,
			'data' => json_encode( [
				'representations' => [
					'la' => [ 'language' => 'la', 'value' => 'Malus baccata' ],
				]
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$form = $result['entity'];
		$this->assertSame( $formId, $form['id'] );
		$this->assertCount( 2, $form['representations'] );
		$this->assertSame( 'Malus', $form['representations']['en']['value'] );
		$this->assertSame( 'Malus baccata', $form['representations']['la']['value'] );
	}

	public function testGivenExistingFormAndRepresentationsWithSpecialLanguage_formIsUpdated() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => $this->formatFormId(
				self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
			),
			'data' => json_encode( [
				'representations' => [
					self::SPECIAL_TERM_LANGUAGE => [
						'language' => self::SPECIAL_TERM_LANGUAGE,
						'value' => 'pineapple'
					],
				]
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	public function testGivenExistingLexemeAddingOfOnlyFormMissingRepresentations_errorIsServed() {
		$this->saveDummyLexemeToDatabase();
		$this->saveEntity( new Item( new ItemId( self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'forms' => [
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
						),
						'remove' => '',
					],
					[
						'id' => $this->formatFormId(
							self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
						),
						'remove' => '',
					],
					[
						'add' => '',
						'grammaticalFeatures' => [ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
					]
				],
			] ),
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Expected exception did not happen.' );
		} catch ( ApiUsageException $exception ) {
			/** @var ApiMessage $message */
			$message = $exception->getMessageObject();

			$this->assertInstanceOf( ApiMessage::class, $message );

			$this->assertSame( 'modification-failed', $message->getApiCode() );
			$this->assertSame(
				'apierror-wikibaselexeme-form-must-have-at-least-one-representation',
				$message->getKey()
			);
			$this->assertSame( [], $message->getParams() );

			$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

			$this->assertSame( self::EXISTING_LEXEME_ID, $lexemeData['id'] );
			$this->assertCount( 2, $lexemeData['forms'], 'entity untouched' );
		}
	}

	public function testGivenExistingFormAndChangeInFormFeatures_formPropertyIsUpdated() {
		$this->saveDummyLexemeToDatabase();
		$this->saveEntity( new Item( new ItemId( self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$formId = $this->formatFormId(
			self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
		);
		$params = [
			'action' => 'wbeditentity',
			'id' => $formId,
			'data' => json_encode( [
				'grammaticalFeatures' => [ self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( $formId, $result['entity']['id'] );
		$this->assertCount( 1, $result['entity']['grammaticalFeatures'] );
		$this->assertSame(
			self::EXISTING_GRAMMATICAL_FEATURE_ITEM_ID,
			$result['entity']['grammaticalFeatures'][0]
		);
	}

	public function testEditSummary_isGenericCommentNoMatterTheChange() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ 'en-gb' => [ 'language' => 'en-gb', 'value' => 'appel' ] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );

		$lookup = WikibaseRepo::getStore()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED );

		$lexemeRevision = $lookup->getEntityRevision( new LexemeId( self::EXISTING_LEXEME_ID ) );

		$revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById(
			$lexemeRevision->getRevisionId()
		);

		$this->assertEquals(
			'/* wbeditentity-update:0| */',
			$revision->getComment()->text
		);
	}

	// TODO: edit statements (all options: add, edit, remove?) with id=L1-F1

	private function formatFormId( $lexemeId, $formId ) {
		return $lexemeId . '-' . $formId;
	}

	private function saveLexemeWithTwoRepresentations() {
		$lexeme = $this->getDummyLexeme();
		$lexeme->getForms()->getById( new FormId( $this->formatFormId(
			self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_1_ID
		) ) )->getRepresentations()->setTerm( new Term(
			self::EXISTING_LEXEME_FORM_1_LANGUAGE2,
			self::EXISTING_LEXEME_FORM_1_TEXT2
		) );
		$lexeme->removeForm( new FormId( $this->formatFormId(
			self::EXISTING_LEXEME_ID, self::EXISTING_LEXEME_FORM_2_ID
		) ) );
		$this->saveEntity( $lexeme );

		return $lexeme;
	}

	private function assertHasStatement( array $expected, array $entity ) {
		$property = $expected['mainsnak']['property'];
		$this->assertArrayHasKey( $property, $entity['claims'] );
		$this->assertCount( 1, $entity['claims'][$property] );

		$claim = $entity['claims'][$property][0];
		$this->assertSame( $expected['mainsnak']['snaktype'], $claim['mainsnak']['snaktype'] );
		$this->assertStatementGuidHasEntityId( $entity['id'], $claim['id'] );
	}

	private function getEntityLookup(): EntityLookup {
		return WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_DISABLED );
	}

}
