<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use Wikibase\Lexeme\Search\FormsField;

/**
 * @covers \Wikibase\Lexeme\Search\FormsField
 */
class LexemeFormsFieldTest extends LexemeFieldTest {

	/**
	 * @return array
	 */
	public function getTestData() {
		return [
			[
				new FormsField(),
				[
					[
						'id' => 'L1-F1',
						'representation' => [ 'Color', 'colour' ],
						'features' => [ 'Q111', 'Q222' ],
					],
					[
						'id' => 'L1-F2',
						'representation' => [ 'testform', 'Test Form' ],
						'features' => [],
					],
				],
			],
		];
	}

}
