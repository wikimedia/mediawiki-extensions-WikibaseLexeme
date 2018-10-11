<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use Wikibase\Lexeme\DataAccess\Search\LexemeCategoryField;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Search\LexemeCategoryField
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
