<?php

namespace Wikibase\Lexeme\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookupFactory;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\MediaWikiContentLanguages;

/**
 * @covers \Wikibase\Lexeme\WikibaseLexemeServices
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServicesTest extends TestCase {

	public function testGetTermLanguages() {
		$languages = WikibaseLexemeServices::getTermLanguages();
		$this->assertInstanceOf( ContentLanguages::class, $languages );

		$baseLanguages = new MediaWikiContentLanguages();

		$this->assertGreaterThan(
			count( $baseLanguages->getLanguages() ),
			count( $languages->getLanguages() ),
			'additional languages appended to default languages'
		);
	}

	public function testGetLanguageNameLookupFactory() {
		$this->assertInstanceOf(
			LexemeLanguageNameLookupFactory::class,
			WikibaseLexemeServices::getLanguageNameLookupFactory()
		);
	}

	public function testGetEditFormChangeOpDeserializer() {
		$this->assertInstanceOf(
			EditFormChangeOpDeserializer::class,
			WikibaseLexemeServices::getEditFormChangeOpDeserializer()
		);
	}

}
