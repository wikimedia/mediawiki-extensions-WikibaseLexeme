<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Prophecy\Argument;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\View\LexemeView;
use Wikibase\Lexeme\View\LexemeViewFactory;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;

/**
 * @covers \Wikibase\Lexeme\View\LexemeViewFactory
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class LexemeViewFactoryTest extends PHPUnit_Framework_TestCase {

	public function testNewLexemeView() {
		/** @var EntityIdHtmlLinkFormatterFactory $formatterFactory */
		$formatterFactory = $this->prophesize( EntityIdHtmlLinkFormatterFactory::class );
		$formatter = $this->prophesize( EntityIdHtmlLinkFormatter::class );
		$formatterFactory->getEntityIdFormatter( Argument::any() )->willReturn( $formatter );

		$factory = new LexemeViewFactory(
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( [] ),
			$this->getMock( EditSectionGenerator::class ),
			$this->getMock( EntityTermsView::class ),
			$formatterFactory->reveal()
		);
		$view = $factory->newLexemeView();
		$this->assertInstanceOf( LexemeView::class, $view );
	}

}
