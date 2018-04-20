<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWiki\Services\ServiceContainer;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\BufferingTermLookup;

/**
 * @license GPL-2.0-or-later
 */
abstract class WikibaseLexemeApiTestCase extends WikibaseApiTestCase {

	/**
	 * @var WikibaseRepo
	 */
	protected $wikibaseRepo;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	public function setUp() {
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';

		parent::setUp();

		$this->wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $this->wikibaseRepo->getEntityStore();
	}

	public function tearDown() {
		parent::tearDown();
		$this->resetTermBuffer();
	}

	private function resetTermBuffer() {
		/**
		 * @var ServiceContainer $services
		 */
		$services = $this->wikibaseRepo->getWikibaseServices();
		$services->disableService( 'TermBuffer' );
		$services->redefineService( 'TermBuffer', function () {
			return new BufferingTermLookup( $this->wikibaseRepo->getStore()->getTermIndex(), 1000 );
		} );
	}

}
