<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use HamcrestPHPUnitIntegration;
use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Services\ServiceContainer;

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
		$repo = WikibaseRepo::getDefaultInstance();

		$services = $repo->getWikibaseServices();
		if ( $this->basedOnMediaWikiServiceContainer( $services ) ) {
			$this->overrideBufferService( $services );
		} else {
			$this->resetInternalLookupService( $services );
		}
	}

	private function basedOnMediaWikiServiceContainer( WikibaseServices $services ) {
		return $services instanceof ServiceContainer;
	}

	private function overrideBufferService( WikibaseServices $services ) {
		$services->disableService( 'TermBuffer' );

		$services->redefineService( 'TermBuffer', function () {
			return new NullPrefetchingTermLookup();
		} );
	}

	private function resetInternalLookupService( WikibaseServices $services ) {
		$internalLookup = ( new \ReflectionClass( $services ) )->getProperty( 'prefetchingTermLookup' );
		$internalLookup->setAccessible( true );
		$internalLookup->setValue( $services, null );
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
