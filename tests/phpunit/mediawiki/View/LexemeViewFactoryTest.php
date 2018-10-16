<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Prophecy\Argument;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\View\LexemeView;
use Wikibase\Lexeme\View\LexemeViewFactory;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\View\EditSectionGenerator;

/**
 * @covers \Wikibase\Lexeme\View\LexemeViewFactory
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class LexemeViewFactoryTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testNewLexemeView() {
		/** @var EntityIdHtmlLinkFormatterFactory $formatterFactory */
		$formatterFactory = $this->prophesize( EntityIdHtmlLinkFormatterFactory::class );
		$formatter = $this->prophesize( EntityIdFormatter::class );
		$formatterFactory->getEntityIdFormatter( Argument::any() )->willReturn( $formatter );

		$factory = new LexemeViewFactory(
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( [] ),
			$this->getMock( EditSectionGenerator::class ),
			$formatterFactory->reveal()
		);
		$view = $factory->newLexemeView();
		$this->assertInstanceOf( LexemeView::class, $view );
	}

}
