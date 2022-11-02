<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use MediaWiki\ResourceLoader\Context;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Presentation\View\TemplateModule;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\TemplateModule
 *
 * @license GPL-2.0-or-later
 */
class TemplateModuleTest extends TestCase {

	/**
	 * @return Context
	 */
	private function getResourceLoaderContext() {
		$context = $this->createMock( Context::class );

		$context->method( 'getLanguage' )
			->willReturn( 'en' );

		return $context;
	}

	public function testGetScriptAddsTemplatesToJavaScriptCode() {
		$templateModule = new TemplateModule();

		$this->assertMatchesRegularExpression(
			'/.*mw\.wbTemplates\.store\.set\( \$\.extend\( .+, mw.wbTemplates.store.values \) \);.*/',
			$templateModule->getScript( $this->getResourceLoaderContext() )
		);
	}

	public function testSupportsURLLoading() {
		$templateModule = new TemplateModule();

		$this->assertFalse( $templateModule->supportsURLLoading() );
	}

	public function testGetDefinitionSummarySetsModificationTimeToModificationTimeOfLexemeTemplates() {
		$expectedMTime = (string)filemtime( __DIR__ . '/../../../../resources/templates.php' );

		$templateModule = new TemplateModule();

		$summary = $templateModule->getDefinitionSummary( $this->getResourceLoaderContext() );
		$this->assertEquals( $expectedMTime, $summary['mtime'] );
	}

}
