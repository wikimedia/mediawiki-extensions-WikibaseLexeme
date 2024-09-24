<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

use Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntitySenseLibrary;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntitySenseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeEntitySenseLibraryTest
	extends WikibaseLexemeLibraryTestCase {

	/** @var string */
	protected static $moduleName = 'WikibaseLexemeEntitySenseLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseLexemeEntitySenseLibraryTests'
				=> __DIR__ . '/WikibaseLexemeEntitySenseLibraryTests.lua',
		];
	}

	public function testParserOutputUsageAccumulatorTracking() {
		$this->makeParserOutputUsageAccumulatorAssertions( WikibaseLexemeEntitySenseLibrary::class );
	}
}
