<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

use Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntityFormLibrary;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntityFormLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeEntityFormLibraryTest
	extends WikibaseLexemeLibraryTestCase {

	/** @var string */
	protected static $moduleName = 'WikibaseLexemeEntityFormLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseLexemeEntityFormLibraryTests'
				=> __DIR__ . '/WikibaseLexemeEntityFormLibraryTests.lua',
		];
	}

	public function testParserOutputUsageAccumulatorTracking() {
		$this->makeParserOutputUsageAccumulatorAssertions( WikibaseLexemeEntityFormLibrary::class );
	}
}
