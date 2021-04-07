<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Store;

use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\Store\Store;
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

	private const LEXEME_ID = 'L1';
	private const FORM_ID = 'F1';
	private const FULL_FORM_ID = 'L1-F1';

	public function testGivenExistingFormId_EntityLookupHasEntityReturnsTrue() {
		$this->saveLexemeWithForm();

		$lookup = WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_DISABLED );

		$this->assertTrue( $lookup->hasEntity( new FormId( self::FULL_FORM_ID ) ) );
	}

	public function testGivenNotExistingFormId_EntityLookupHasEntityReturnsFalse() {
		$this->saveLexemeWithoutForm();

		$lookup = WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_DISABLED );

		$this->assertFalse( $lookup->hasEntity( new FormId( self::FULL_FORM_ID ) ) );
	}

	private function saveLexemeWithForm() {
		$this->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )
				->withForm(
					NewForm::havingId( self::FORM_ID )->build()
				)->build()
		);
	}

	private function saveLexemeWithoutForm() {
		$this->saveEntity(
			NewLexeme::havingId( self::LEXEME_ID )->build()
		);
	}

}
