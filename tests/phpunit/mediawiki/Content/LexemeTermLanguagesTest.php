<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages
 *
 * @license GPL-2.0-or-later
 */
class LexemeTermLanguagesTest extends TestCase {

	public function testGetLanguages_hasDefaults() {
		$languages = new LexemeTermLanguages( [] );
		$this->assertGreaterThan( 0, count( $languages->getLanguages() ) );
	}

	public function testGetLanguages_hasAdditionalLanguageCodes() {
		$languages = new LexemeTermLanguages( [ 'und', 'zxx' ] );
		$codes = $languages->getLanguages();

		$this->assertContains( 'und', $codes );
		$this->assertContains( 'zxx', $codes );
	}

	public function testHasLanguage_hasTheMostObviousDefault() {
		$languages = new LexemeTermLanguages( [] );
		$this->assertTrue( $languages->hasLanguage( 'en' ) );
	}

	public function testHasLanguage_hasAdditionalLanguageCode() {
		$languages = new LexemeTermLanguages( [ 'zxx' ] );
		$this->assertTrue( $languages->hasLanguage( 'zxx' ) );
	}

	public function testHasLanguage_doesntHaveLanguageCodeNotPassed() {
		$languages = new LexemeTermLanguages( [] );
		$this->assertFalse( $languages->hasLanguage( 'impossible' ) );
	}

}
