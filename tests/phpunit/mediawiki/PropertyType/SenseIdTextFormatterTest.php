<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\PropertyType;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\PropertyType\SenseIdTextFormatter;

/**
 * @covers \Wikibase\Lexeme\PropertyType\SenseIdTextFormatter
 *
 * @license GPL-2.0-or-later
 */
class SenseIdTextFormatterTest extends TestCase {

	public function testFormatId() {
		$senseId = new SenseId( 'L10-S20' );
		$formatter = new SenseIdTextFormatter();

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame( 'L10-S20', $result );
	}

}
