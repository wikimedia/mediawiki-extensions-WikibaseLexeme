<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

use Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntityLexemeLibrary;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntityLexemeLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeEntityLexemeLibraryTest
	extends WikibaseLexemeLibraryTestCase {

	/** @var string */
	protected static $moduleName = 'WikibaseLexemeEntityLexemeLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseLexemeEntityLexemeLibraryTests'
				=> __DIR__ . '/WikibaseLexemeEntityLexemeLibraryTests.lua',
		];
	}

	public function testParserOutputUsageAccumulatorTracking() {
		$this->makeParserOutputUsageAccumulatorAssertions( WikibaseLexemeEntityLexemeLibrary::class );
	}
}
