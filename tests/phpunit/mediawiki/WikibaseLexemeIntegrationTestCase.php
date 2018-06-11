<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use HamcrestPHPUnitIntegration;
use MediaWiki\Services\ServiceContainer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\BufferingTermLookup;

/**
 * @license GPL-2.0-or-later
 */
abstract class WikibaseLexemeIntegrationTestCase extends \MediaWikiLangTestCase {

	use HamcrestPHPUnitIntegration;

	public function tearDown() {
		parent::tearDown();
		$this->resetTermBuffer();
	}

	private function resetTermBuffer() {
		$repo = WikibaseRepo::getDefaultInstance();
		/**
		 * @var ServiceContainer $services
		 */
		$services = $repo->getWikibaseServices();
		$services->disableService( 'TermBuffer' );
		$services->redefineService( 'TermBuffer', function () use ( $repo ) {
			return new BufferingTermLookup( $repo->getStore()->getTermIndex(), 1000 );
		} );
	}

}
