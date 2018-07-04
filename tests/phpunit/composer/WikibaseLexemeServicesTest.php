<?php

namespace Wikibase\Lexeme\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServicesTest extends TestCase {

	public function testGetTermLanguages() {
		$this->assertInstanceOf( ContentLanguages::class, WikibaseLexemeServices::getTermLanguages() );
	}

}
