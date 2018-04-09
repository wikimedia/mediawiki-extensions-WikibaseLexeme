<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use Exception;
use User;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;

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

	const EXISTING_LEXEME_ID = 'L100';
	const EXISTING_LEXEME_LEMMA = 'apple';
	const EXISTING_LEXEME_LEMMA_LANGUAGE = 'en';
	const EXISTING_LEXEME_LANGUAGE_ITEM_ID = 'Q66';
	const EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID = 'Q55';
	const EXISTING_LEXEME_FORM_1_ID = 'F1';
	const EXISTING_LEXEME_FORM_1_LANGUAGE = 'en';
	const EXISTING_LEXEME_FORM_1_TEXT = 'crabapple';
	const EXISTING_LEXEME_FORM_2_ID = 'F2';
	const EXISTING_LEXEME_FORM_2_LANGUAGE = 'en';
	const EXISTING_LEXEME_FORM_2_TEXT = 'Malus';

	public function testGivenNewParameterAndValidDataAreProvided_newLexemeIsCreated() {
		$params = [
			'action' => 'wbeditentity',
			'new' => 'lexeme',
			'data' => json_encode( [
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
				'language' => 'Q100',
				'lexicalCategory' => 'Q200',
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertArrayHasKey( 'id', $result['entity'] );
		$this->assertSame( 'lexeme', $result['entity']['type'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$id = $result['entity']['id'];

		$lexemeData = $this->loadEntity( $id );

		$this->assertEntityFieldsEqual(
			[
				'type' => 'lexeme',
				'id' => $id,
				'claims' => [],
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
				'language' => 'Q100',
				'lexicalCategory' => 'Q200',
			],
			$lexemeData
		);
	}

	private function getDummyLexeme( $id = self::EXISTING_LEXEME_ID ) {
		return NewLexeme::havingId( $id )
			->withLemma( self::EXISTING_LEXEME_LEMMA_LANGUAGE, self::EXISTING_LEXEME_LEMMA )
			->withLexicalCategory( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID )
			->withLanguage( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID )
			->withForm( new Form(
				new FormId(
					$this->formatFormId(
						$id,
						self::EXISTING_LEXEME_FORM_1_ID
					)
				),
				new TermList( [
					new Term(
						self::EXISTING_LEXEME_FORM_1_LANGUAGE,
						self::EXISTING_LEXEME_FORM_1_TEXT
					)
				] ),
				[]
			) )->withForm( new Form(
				new FormId(
					$this->formatFormId(
						$id,
						self::EXISTING_LEXEME_FORM_2_ID
					)
				),
				new TermList( [
					new Term(
						self::EXISTING_LEXEME_FORM_2_LANGUAGE,
						self::EXISTING_LEXEME_FORM_2_TEXT
					)
				] ),
				[]
			) )
			->build();
	}

	private function saveDummyLexemeToDatabase() {
		$this->entityStore->saveEntity(
			$this->getDummyLexeme(),
			self::class,
			$this->getMock( User::class )
		);
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
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		$this->assertSame( 'lexeme', $result['entity']['type'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertEntityFieldsEqual(
			[
				'type' => 'lexeme',
				'id' => self::EXISTING_LEXEME_ID,
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
			],
			$lexemeData
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
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'lemmas' => [
					'en' => [ 'language' => 'en', 'value' => 'apple' ],
					'en-gb' => [ 'language' => 'en-gb', 'value' => 'appel' ],
				]
			],
			$lexemeData
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
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'lemmas' => [
					'en' => [ 'language' => 'en', 'value' => 'apple' ],
				]
			],
			$lexemeData
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
			$this->getMock( User::class )
		);
	}

	public function testGivenIdOfExistingLexemeAndLemmaDataAsNumberIndexArray_lemmaIsChanged() {
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ [ 'language' => 'en', 'value' => 'worm' ] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
			],
			$lexemeData
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

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'language' => 'Q333',
			],
			$lexemeData
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

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'lexicalCategory' => 'Q333',
			],
			$lexemeData
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

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'language' => 'Q303',
				'lexicalCategory' => 'Q606',
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'worm' ] ],
			],
			$lexemeData
		);
	}

	public function testGivenIdOfExistingLexemeAndStatementData_statementIsAdded() {
		$this->saveDummyLexemeToDatabase();

		$property = new Property( new PropertyId( 'P909' ), null, 'test' );
		$this->entityStore->saveEntity( $property, self::class, $this->getMock( User::class ) );

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'claims' => [ [
					'mainsnak' => [ 'snaktype' => 'novalue', 'property' => 'P909' ],
					'type' => 'statement',
					'rank' => 'normal',
				] ],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertSame( self::EXISTING_LEXEME_ID, $lexemeData['id'] );
		$this->assertSame( 'P909', $lexemeData['claims']['P909'][0]['mainsnak']['property'] );
		$this->assertSame( 'normal', $lexemeData['claims']['P909'][0]['rank'] );
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

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( self::EXISTING_LEXEME_ID, $result['entity']['id'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertEntityFieldsEqual(
			[
				'id' => self::EXISTING_LEXEME_ID,
				'language' => self::EXISTING_LEXEME_LANGUAGE_ITEM_ID,
				'lexicalCategory' => self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID,
				'lemmas' => [ 'en-gb' => [ 'language' => 'en-gb', 'value' => 'appel' ] ],
			],
			$lexemeData
		);
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
				'missing-language'
			],
			'no language in lemma change request (remove)' => [
				[ 'lemmas' => [ 'en' => [ 'remove' => '' ] ] ],
				'missing-language'
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

	public function provideInvalidDataWithClear() {
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
		];
	}

	/**
	 * @dataProvider provideInvalidDataWithClear
	 */
	public function testGivenInvalidDataInClearRequest_errorIsReported( array $dataArgs ) {
		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'clear' => true,
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
		$this->assertSame(
			'failed-save',
			$exception->getStatusValue()->getErrors()[0]['message']->getApiCode()
		);
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
						'remove' => ''
					]
				],
			] ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertSame( self::EXISTING_LEXEME_ID, $lexemeData['id'] );

		$this->assertCount( 1, $lexemeData['forms'] );
		$this->assertSame(
			$this->formatFormId(
				self::EXISTING_LEXEME_ID,
				self::EXISTING_LEXEME_FORM_2_ID
			),
			$lexemeData['forms'][0]['id']
		);
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

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertSame( self::EXISTING_LEXEME_ID, $lexemeData['id'] );
		$this->assertCount( 0, $lexemeData['forms'] );
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

		$this->doTestQueryExceptions( $params, [
			'code' => 'wikibaselexeme-api-error-parameter-not-form-id'
		] );

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

		$this->doTestQueryExceptions( $params, [
			'code' => 'wikibaselexeme-api-error-parameter-required'
		] );
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

		$this->doTestQueryExceptions( $params, [
			'code' => 'wikibaselexeme-api-error-parameter-not-form-id'
		] );
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

		$this->doTestQueryExceptions( $params, [
			'code' => 'bad-param-type'
		] );
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

		$this->entityStore->saveEntity( $secondLexeme, self::class, $this->getMock( User::class ) );

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
			'code' => 'modification-failed'
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
			'code' => 'modification-failed'
		] );
	}

	/**
	 * @dataProvider provideDataRequiringEditPermissions
	 *
	 * @expectedException ApiUsageException
	 * @expectedExceptionMessage You're not allowed to edit this wiki through the API.
	 */
	public function testEditOfLexemeWithoutEditPermission_violationIsReported( array $editData ) {
		$this->saveDummyLexemeToDatabase();

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
			'*' => [
				'read' => true,
				'edit' => false
			]
		] );

		$this->doApiRequestWithToken( [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( $editData ),
		], null, self::createTestUser()->getUser() );
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

	private function formatFormId( $lexemeId, $formId ) {
		return $lexemeId . '-' . $formId;
	}

}
