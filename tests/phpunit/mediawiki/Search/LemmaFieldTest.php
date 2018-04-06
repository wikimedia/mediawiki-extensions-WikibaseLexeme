<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use Wikibase\Lexeme\Search\LemmaField;

/**
 * @covers \Wikibase\Lexeme\Search\LemmaField
 */
class LemmaFieldTest extends LexemeFieldTest {

	/**
	 * @return array
	 */
	public function getTestData() {
		return [
			[
				new LemmaField(),
				[ "Test Lemma" ]
			]
		];
	}

}
