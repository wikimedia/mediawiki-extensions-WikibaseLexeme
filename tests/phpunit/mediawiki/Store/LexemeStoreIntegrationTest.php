<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeStoreIntegrationTest extends WikibaseLexemeIntegrationTestCase {

	const LEXEME_ID = 'L1';
	const FORM_ID = 'F1';
	const FULL_FORM_ID = 'L1-F1';

	public function testGivenExistingFormId_EntityLookupHasEntityReturnsTrue() {
		$this->saveLexemeWithForm();

		$lookup = WikibaseRepo::getDefaultInstance()->getEntityLookup( 'uncached' );

		$this->assertTrue( $lookup->hasEntity( new FormId( self::FULL_FORM_ID ) ) );
	}

	public function testGivenNotExistingFormId_EntityLookupHasEntityReturnsFalse() {
		$this->saveLexemeWithoutForm();

		$lookup = WikibaseRepo::getDefaultInstance()->getEntityLookup( 'uncached' );

		$this->assertFalse( $lookup->hasEntity( new FormId( self::FULL_FORM_ID ) ) );
	}

	private function saveLexemeWithForm() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$lexeme = NewLexeme::havingId( self::LEXEME_ID )
			->withForm(
				NewForm::havingId( self::FORM_ID )->build()
			)->build();

		$store->saveEntity(
			$lexeme,
			self::class,
			$this->getTestUser()->getUser()
		);
	}

	private function saveLexemeWithoutForm() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$lexeme = NewLexeme::havingId( self::LEXEME_ID )->build();

		$store->saveEntity(
			$lexeme,
			self::class,
			$this->getTestUser()->getUser()
		);
	}

}
