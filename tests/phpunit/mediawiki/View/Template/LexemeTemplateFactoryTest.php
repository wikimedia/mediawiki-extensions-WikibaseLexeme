<?php

namespace Wikibase\Lexeme\Tests\Mediawiki\View\Template;

use PHPUnit_Framework_TestCase;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;

/**
 * @covers \Wikibase\Lexeme\View\Template\LexemeTemplateFactory
 *
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class TemplateFactoryTest extends PHPUnit_Framework_TestCase {

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
