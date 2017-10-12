<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use ResourceLoaderContext;
use Wikibase\Lexeme\View\TemplateModule;

/**
 * @covers \Wikibase\Lexeme\View\TemplateModule
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class TemplateModuleTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return ResourceLoaderContext
	 */
	private function getResourceLoaderContext() {
		$context = $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();

		$context->method( 'getLanguage' )
			->will( $this->returnValue( 'en' ) );

		return $context;
	}

	public function testGetScriptAddsTemplatesToJavaScriptCode() {
		$templateModule = new TemplateModule();

		$this->assertRegExp(
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
