<?php

namespace Wikibase\Lexeme\Tests\Unit\Merge\Validator;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Merge\Validator\NoConflictingTermListValues;

/**
 * @covers \Wikibase\Lexeme\Domain\Merge\Validator\NoConflictingTermListValues
 *
 * @license GPL-2.0-or-later
 */
class NoConflictingTermListValuesTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideSamples
	 */
	public function testValidate( $expected, $source, $target ) {
		$validator = new NoConflictingTermListValues();

		$this->assertSame( $expected, $validator->validate( $source, $target ) );
	}

	public function provideSamples() {
		yield [
			true,
			new TermList(),
			new TermList()
		];

		yield [
			true,
			new TermList( [ new Term( 'en', 'lorem' ) ] ),
			new TermList( [ new Term( 'en', 'lorem' ) ] ),
		];

		yield [
			false,
			new TermList( [ new Term( 'en-gb', 'foo' ) ] ),
			new TermList( [ new Term( 'en-gb', 'bar' ) ] ),
		];
	}

}
