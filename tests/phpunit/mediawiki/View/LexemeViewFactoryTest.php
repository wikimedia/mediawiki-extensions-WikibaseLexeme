<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use Language;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\Presentation\View\LexemeView;
use Wikibase\Lexeme\Presentation\View\LexemeViewFactory;
use Wikibase\Lib\Store\EntityInfo;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\LexemeViewFactory
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class LexemeViewFactoryTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testNewLexemeView() {
		$factory = new LexemeViewFactory(
			Language::factory( 'en' ),
			new LanguageFallbackChain( [] ),
			new EntityInfo( [] ),
			'wikibase-save'
		);
		$view = $factory->newLexemeView();
		$this->assertInstanceOf( LexemeView::class, $view );
	}

}
