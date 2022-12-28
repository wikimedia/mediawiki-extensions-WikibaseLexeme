<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Presentation\View\LexemeView;
use Wikibase\Lexeme\Presentation\View\LexemeViewFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\LexemeViewFactory
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class LexemeViewFactoryTest extends TestCase {

	public function testNewLexemeView() {
		$factory = new LexemeViewFactory(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ),
			'wikibase-save'
		);
		$view = $factory->newLexemeView();
		$this->assertInstanceOf( LexemeView::class, $view );
	}

}
