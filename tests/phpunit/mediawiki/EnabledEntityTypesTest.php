<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWikiIntegrationTestCase;
use Wikibase\Client\WikibaseClient;
use Wikibase\Repo\WikibaseRepo;

/**
 * Trivial test to assert that Wikibase knows about our entity types.
 *
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class EnabledEntityTypesTest extends MediaWikiIntegrationTestCase {

	public function testRepoEnabledEntityTypes() {
		$this->markTestSkippedIfExtensionNotLoaded( 'WikibaseRepository' );

		$this->assertHasLexemeEntityTypes( WikibaseRepo::getEnabledEntityTypes() );
	}

	public function testEntityTypeDefinitions_client() {
		$this->assertHasLexemeEntityTypes(
			WikibaseClient::getEntityTypeDefinitions()->getEntityTypes()
		);
	}

	public function testEntityTypeDefinitions_repo() {
		$this->markTestSkippedIfExtensionNotLoaded( 'WikibaseRepository' );

		$this->assertHasLexemeEntityTypes(
			WikibaseRepo::getEntityTypeDefinitions()->getEntityTypes()
		);
	}

	private function assertHasLexemeEntityTypes( array $actual ) {
		$this->assertContains( 'lexeme', $actual );
		// sub entity types
		$this->assertContains( 'form', $actual );
		$this->assertContains( 'sense', $actual );
	}

}
