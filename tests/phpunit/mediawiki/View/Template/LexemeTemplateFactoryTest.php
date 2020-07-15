<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View\Template;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeTemplateFactoryTest extends TestCase {

	private function newInstance() {
		return new LexemeTemplateFactory( [ 'basic' => '$1' ] );
	}

	/**
	 * @dataProvider renderParamsProvider
	 */
	public function testRender( $params, $expected ) {
		$rendered = $this->newInstance()->render( 'basic', $params );
		$this->assertSame( $expected, $rendered );
	}

	public function renderParamsProvider() {
		return [
			[ '<PARAM>', '<PARAM>' ],
			[ [], '$1' ],
			[ [ '<PARAM>' ], '<PARAM>' ],
			[ [ '<PARAM>', 'ignored' ], '<PARAM>' ],
		];
	}

}
