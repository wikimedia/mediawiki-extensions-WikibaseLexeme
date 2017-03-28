<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\EditEntity
 *
 * @license GPL-2.0+
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 * @group WikibaseLexeme
 */
class LexemeEditEntityTest extends WikibaseApiTestCase {

	const EXISTING_LEXEME_ID = 'L100';
	const EXISTING_LEXEME_LEMMA = 'apple';
	const EXISTING_LEXEME_LEMMA_LANGUAGE = 'en';
	const EXISTING_LEXEME_LANGUAGE_ITEM_ID = 'Q66';
	const EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID = 'Q55';

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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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

	private function saveDummyLexemeToDatabase() {
		$lexeme = new Lexeme(
			new LexemeId( self::EXISTING_LEXEME_ID ),
			new TermList( [
				new Term( self::EXISTING_LEXEME_LEMMA_LANGUAGE, self::EXISTING_LEXEME_LEMMA ),
			] ),
			new ItemId( self::EXISTING_LEXEME_LANGUAGE_ITEM_ID ),
			new ItemId( self::EXISTING_LEXEME_LEXICAL_CATEGORY_ITEM_ID )
		);

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$store->saveEntity( $lexeme, self::class, $this->getMock( User::class ) );
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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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
		$this->saveDummyLexemeToDatabase();

		$params = [
			'action' => 'wbeditentity',
			'id' => self::EXISTING_LEXEME_ID,
			'data' => json_encode( [
				'lemmas' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ],
			] ),
		];

		list ( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
		// TODO: Also check lexeme fields are returned in the response when they're returned (T160504)

		$lexemeData = $this->loadEntity( self::EXISTING_LEXEME_ID );

		$this->assertSame( self::EXISTING_LEXEME_ID, $lexemeData['id'] );
		$this->assertArrayNotHasKey( 'lemmas', $lexemeData );
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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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

		$property = new Property( new PropertyId( 'P909' ), null, '' );
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $property, self::class, $this->getMock( User::class ) );

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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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

		list ( $result, ) = $this->doApiRequestWithToken( $params );

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

	// TODO: add tests for invalid input

	// TODO: add tests for trying to remove language, lexcat

	private function assertEntityFieldsEqual( array $expected, array $actual ) {
		foreach ( array_keys( $expected ) as $field ) {
			$this->assertArrayHasKey( $field, $actual );
			$this->assertSame( $expected[$field], $actual[$field] );
		}
	}

}
