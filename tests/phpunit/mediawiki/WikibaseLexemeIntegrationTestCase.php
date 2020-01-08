<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use HamcrestPHPUnitIntegration;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\NullTermIndex;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\BufferingTermLookup;
use Wikimedia\Services\ServiceContainer;

/**
 * @license GPL-2.0-or-later
 */
abstract class WikibaseLexemeIntegrationTestCase extends \MediaWikiLangTestCase {

	use HamcrestPHPUnitIntegration;

	public function tearDown() : void {
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
			return new BufferingTermLookup( new NullTermIndex(), 1000 );
		} );
	}

	protected function getEntityStore() {
		// When we request an EntityStore via this method assume we will be inserting something.
		$this->tablesUsed[] = 'page';
		return WikibaseRepo::getDefaultInstance()->getEntityStore();
	}

	protected function saveEntity( EntityDocument $entity ) {
		$this->getEntityStore()->saveEntity(
			$entity,
			static::class,
			$this->getTestUser()->getUser()
		);
	}

}
