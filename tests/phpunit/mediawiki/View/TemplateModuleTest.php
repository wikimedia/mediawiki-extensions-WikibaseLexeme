<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Presentation\View\TemplateModule;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\TemplateModule
 *
 * @license GPL-2.0-or-later
 */
class TemplateModuleTest extends TestCase {

	/**
	 * @return RL\Context
	 */
	private function getResourceLoaderContext() {
		$context = $this->createMock( RL\Context::class );

		$context->method( 'getLanguage' )
			->willReturn( 'en' );

		return $context;
	}

	public function testGetScriptAddsTemplatesToJavaScriptCode() {
		$this->assertMatchesRegularExpression(
			'/.*mw\.wbTemplates\.store\.set\( \$\.extend\( .+, mw.wbTemplates.store.values \) \);.*/',
			TemplateModule::getScript( $this->getResourceLoaderContext() )
		);
	}

	public function testGetVersionReturnsFilePath() {
		$version = TemplateModule::getVersion( $this->getResourceLoaderContext() );
		$this->assertEquals( new RL\FilePath( 'templates.php' ), $version );
	}

}
