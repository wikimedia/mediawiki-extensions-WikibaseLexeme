<?php

namespace Wikibase\Lexeme\Tests\Specials;

use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Specials\SpecialNewLexeme;
use Wikibase\Repo\Tests\Specials\SpecialNewEntityTest;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Lexeme\Specials\SpecialNewLexeme
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class SpecialNewLexemeTest extends SpecialNewEntityTest {

	protected function newSpecialPage() {
		return new SpecialNewLexeme( $this->copyrightView );
	}

	public function testAllNecessaryFormFieldsArePresent_WhenRendered() {

		list( $html ) = $this->executeSpecialPage();

		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEMMA_LANGUAGE );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEMMA );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEXICAL_CATEGORY );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEXEME_LANGUAGE );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function provideValidEntityCreationRequests() {

		$existingItemId = 'Q1';
		$this->givenItemExists( $existingItemId );

		return [
			'everything is set' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma text',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => $existingItemId,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => $existingItemId,
				],
			],
		];
	}

	public function provideInvalidEntityCreationRequests() {
		$nonexistentItemId = 'Q100';

		$existingItemId = 'Q1';
		$this->givenItemExists( $existingItemId );

		return [
			'unknown language' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'some-weird-language',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => $existingItemId,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => $existingItemId,
				],
				'language code was not recognized',
			],
			'empty lemma' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => '',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => $existingItemId,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => $existingItemId,
				],
				'value is required',
			],
			'lexical category has wrong format' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => 'x',
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => $existingItemId,
				],
				'invalid format',
			],
			'lexeme language has wrong format' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => $existingItemId,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => 'x',
				],
				'invalid format',
			],
			'lexical category does not exist' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => $nonexistentItemId,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => $existingItemId,
				],
				'does not exist',
			],
			'lexeme language does not exist' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => $existingItemId,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => $nonexistentItemId,
				],
				'does not exist',
			],
			'lexeme language is not set' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => $existingItemId,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => '',
				],
				'invalid format',
			],
			'lexical category is not set' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => '',
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => $existingItemId,
				],
				'invalid format',
			],
		];
	}

	/**
	 * @param string $url
	 * @return EntityId
	 */
	protected function extractEntityIdFromUrl( $url ) {
		$serialization = preg_replace( '@^.*(L\d+)$@', '$1', $url );

		return new LexemeId( $serialization );
	}

	protected function assertEntityMatchesFormData( array $form, EntityDocument $entity ) {
		$this->assertInstanceOf( Lexeme::class, $entity );
		/** @var Lexeme $entity */

		$language = $form[ SpecialNewLexeme::FIELD_LEMMA_LANGUAGE ];
		self::assertEquals(
			$form[ SpecialNewLexeme::FIELD_LEMMA ],
			$entity->getLemmas()->getByLanguage( $language )->getText()
		);

		if ( $form[ SpecialNewLexeme::FIELD_LEXICAL_CATEGORY ] ) {
			self::assertEquals(
				$form[ SpecialNewLexeme::FIELD_LEXICAL_CATEGORY ],
				$entity->getLexicalCategory()->getSerialization()
			);
		}

		if ( $form[ SpecialNewLexeme::FIELD_LEXEME_LANGUAGE ] ) {
			self::assertEquals(
				$form[ SpecialNewLexeme::FIELD_LEXEME_LANGUAGE ],
				$entity->getLanguage()->getSerialization()
			);
		}
	}

	/**
	 * @param string $itemId
	 */
	private function givenItemExists( $itemId ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$existingItem = new Item( new ItemId( $itemId ) );

		$editEntityFactory = $wikibaseRepo->newEditEntityFactory()
			->newEditEntity( new User(), $existingItem );

		$editEntityFactory->attemptSave( '', EDIT_NEW, false );
	}

}
