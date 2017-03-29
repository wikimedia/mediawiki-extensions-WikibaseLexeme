<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use PHPUnit_Framework_TestCase;
use Wikibase\Lexeme\Search\LexemeFieldDefinitions;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LexemeFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetFields() {
		$fieldDefinitions = new LexemeFieldDefinitions();

		$expectedKeys = [ 'statement_count' ];

		$this->assertSame( $expectedKeys, array_keys( $fieldDefinitions->getFields() ) );
	}

}
