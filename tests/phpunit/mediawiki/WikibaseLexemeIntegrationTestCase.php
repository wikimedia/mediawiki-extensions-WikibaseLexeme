<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use HamcrestPHPUnitIntegration;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
abstract class WikibaseLexemeIntegrationTestCase extends \MediaWikiLangTestCase {

	use HamcrestPHPUnitIntegration;

	protected function tearDown() : void {
		$this->resetTermBuffer();
		parent::tearDown();
	}

	private function resetTermBuffer() {
		if ( $this->getServiceContainer()->has( 'WikibaseRepo.PrefetchingTermLookup' ) ) {
			$this->resetServices();
		} else {
			$services = WikibaseRepo::getWikibaseServices();
			$internalLookup = ( new \ReflectionClass( $services ) )->getProperty( 'prefetchingTermLookup' );
			$internalLookup->setAccessible( true );
			$internalLookup->setValue( $services, null );
		}
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
