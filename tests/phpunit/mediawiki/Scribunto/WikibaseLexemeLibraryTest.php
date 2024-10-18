<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeLibraryTest extends WikibaseLexemeLibraryTestCase {

	/** @var string */
	protected static $moduleName = 'WikibaseLexemeLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseLexemeLibraryTests' => __DIR__ . '/WikibaseLexemeLibraryTests.lua',
		];
	}

}
