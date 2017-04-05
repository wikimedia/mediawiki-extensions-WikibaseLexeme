<?php

namespace Wikibase\Lexeme\Tests\Mediawiki\View\Template;

use PHPUnit_Framework_TestCase;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\Lexeme\View\Template\LexemeTemplateFactory
 *
 * @group Wikibase
 * @group WikibaseView
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class TemplateFactoryTest extends PHPUnit_Framework_TestCase {

	private function newInstance() {
		return new LexemeTemplateFactory( new TemplateRegistry( [ 'basic' => '$1' ] ) );
	}

	public function testGetDefaultInstance() {
		$instance = LexemeTemplateFactory::getDefaultInstance();
		$this->assertInstanceOf( LexemeTemplateFactory::class, $instance );
	}

	public function testGetTemplates() {
		$templates = $this->newInstance()->getTemplates();
		$this->assertSame( [ 'basic' => '$1' ], $templates );
	}

	public function testGet() {
		$template = $this->newInstance()->get( 'basic', [ '<PARAM>' ] );
		$this->assertSame( 'basic', $template->getKey() );
		$this->assertSame( [ '<PARAM>' ], $template->getParams() );
		$this->assertSame( '<PARAM>', $template->plain() );
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
