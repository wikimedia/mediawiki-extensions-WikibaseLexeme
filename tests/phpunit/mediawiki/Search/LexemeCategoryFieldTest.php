<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use Wikibase\Lexeme\Search\LexemeCategoryField;

/**
 * @covers \Wikibase\Lexeme\Search\LexemeCategoryField
 */
class LexemeCategoryFieldTest extends LexemeFieldTest {

	/**
	 * @return array
	 */
	public function getTestData() {
		return [
			[
				new LexemeCategoryField(),
				self::CATEGORY_ID
			]
		];
	}

}
