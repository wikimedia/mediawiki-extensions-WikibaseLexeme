<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use Wikibase\Lexeme\DataAccess\Search\LemmaField;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Search\LemmaField
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
