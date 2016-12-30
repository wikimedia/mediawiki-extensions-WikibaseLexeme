<?php

namespace Wikibase\Lexeme\Tests\Specials;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Specials\SpecialNewLexeme;
use Wikibase\Repo\Tests\Specials\SpecialNewEntityTest;

/**
 * @covers Wikibase\Lexeme\Specials\SpecialNewLexeme
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class SpecialNewLemexeTest extends SpecialNewEntityTest {

	const FIELD_LEMMA_LANGUAGE = 'lemma-language';
	const FIELD_LEMMA = 'lemma';
	const FIELD_LEXICAL_CATEGORY = 'lexicalcategory';
	const FIELD_LEXEME_LANGUAGE = 'lexeme-language';

	protected function newSpecialPage() {
		return new SpecialNewLexeme();
	}

	public function testAllNecessaryFormFieldsArePresent_WhenRendered() {

		list( $html ) = $this->executeSpecialPage();

		$this->assertHtmlContainsInputWithName( $html, self::FIELD_LEMMA_LANGUAGE );
		$this->assertHtmlContainsInputWithName( $html, self::FIELD_LEMMA );
		$this->assertHtmlContainsInputWithName( $html, self::FIELD_LEXICAL_CATEGORY );
		$this->assertHtmlContainsInputWithName( $html, self::FIELD_LEXEME_LANGUAGE );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function provideValidEntityCreationRequests() {
		return [
			'lemma is set' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'en',
					self::FIELD_LEMMA => 'some lemma text',
					self::FIELD_LEXICAL_CATEGORY => '',
					self::FIELD_LEXEME_LANGUAGE => '',
				],
			],
			'another lemma language' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'fr',
					self::FIELD_LEMMA => 'some lemma text',
					self::FIELD_LEXICAL_CATEGORY => '',
					self::FIELD_LEXEME_LANGUAGE => '',
				],
			],
			'lexical category is set' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'en',
					self::FIELD_LEMMA => 'some lemma text',
					self::FIELD_LEXICAL_CATEGORY => 'Q1',
					self::FIELD_LEXEME_LANGUAGE => '',
				],
			],
			'lexeme language is set' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'en',
					self::FIELD_LEMMA => 'some lemma text',
					self::FIELD_LEXICAL_CATEGORY => '',
					self::FIELD_LEXEME_LANGUAGE => 'Q1',
				],
			],
		];
	}

	public function provideInvalidEntityCreationRequests() {
		$this->markTestSkipped( "Fixes in the code needed" );

		$nonexistentItemId = 'Q100';

		return [
			'unknown language' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'some-weird-language',
					self::FIELD_LEMMA => 'some lemma',
					self::FIELD_LEXICAL_CATEGORY => '',
					self::FIELD_LEXEME_LANGUAGE => '',
				],
				'language code was not recognized',
			],
			'empty lemma' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'en',
					self::FIELD_LEMMA => '',
					self::FIELD_LEXICAL_CATEGORY => '',
					self::FIELD_LEXEME_LANGUAGE => '',
				],
				'you need to fill',
			],
			'lexical category has wrong format' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'en',
					self::FIELD_LEMMA => 'some lemma',
					self::FIELD_LEXICAL_CATEGORY => 'x',
					self::FIELD_LEXEME_LANGUAGE => '',
				],
				'???',
			],
			'lexeme language has wrong format' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'en',
					self::FIELD_LEMMA => 'some lemma',
					self::FIELD_LEXICAL_CATEGORY => '',
					self::FIELD_LEXEME_LANGUAGE => 'x',
				],
				'???',
			],
			'lexical category does not exist' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'en',
					self::FIELD_LEMMA => 'some lemma',
					self::FIELD_LEXICAL_CATEGORY => $nonexistentItemId,
					self::FIELD_LEXEME_LANGUAGE => '',
				],
				'???',
			],
			'lexeme language does not exist' => [
				[
					self::FIELD_LEMMA_LANGUAGE => 'en',
					self::FIELD_LEMMA => 'some lemma',
					self::FIELD_LEXICAL_CATEGORY => '',
					self::FIELD_LEXEME_LANGUAGE => $nonexistentItemId,
				],
				'???',
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

		$language = $form[ self::FIELD_LEMMA_LANGUAGE ];
		self::assertEquals(
			$form[ self::FIELD_LEMMA ],
			$entity->getLemmas()->getByLanguage( $language )->getText()
		);

		if ( $form[ self::FIELD_LEXICAL_CATEGORY ] ) {
			self::assertEquals(
				$form[ self::FIELD_LEXICAL_CATEGORY ],
				$entity->getLexicalCategory()->getSerialization()
			);
		}

		if ( $form[ self::FIELD_LEXEME_LANGUAGE ] ) {
			self::assertEquals(
				$form[ self::FIELD_LEXEME_LANGUAGE ],
				$entity->getLanguage()->getSerialization()
			);
		}
	}

}
