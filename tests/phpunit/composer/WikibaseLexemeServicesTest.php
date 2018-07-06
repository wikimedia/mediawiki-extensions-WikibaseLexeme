<?php

namespace Wikibase\Lexeme\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\ContentLanguages;

/**
 * @covers \Wikibase\Lexeme\WikibaseLexemeServices
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServicesTest extends TestCase {

	public function testGetTermLanguages() {
		$languages = WikibaseLexemeServices::getTermLanguages();
		$this->assertInstanceOf( ContentLanguages::class, $languages );
		$this->assertEmpty(
			array_diff(
				WikibaseLexemeServices::getAdditionalLanguages(),
				$languages->getLanguages()
			),
			'additional languages correctly injected into TermLanguages'
		);
	}

	public function testGetLanguageNameLookup() {
		$this->assertInstanceOf(
			LexemeLanguageNameLookup::class,
			WikibaseLexemeServices::getLanguageNameLookup()
		);
	}

	public function testGetAdditionalLanguages() {
		$this->assertInternalType(
			'array',
			WikibaseLexemeServices::getAdditionalLanguages()
		);
	}

}
