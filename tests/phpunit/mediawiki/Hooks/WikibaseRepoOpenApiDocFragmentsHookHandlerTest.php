<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks;

use Wikibase\Lexeme\Hooks\WikibaseRepoOpenApiDocFragmentsHookHandler;
use Wikibase\Lexeme\Tests\MediaWiki\RestApi\RestRoutes;
use Wikibase\Repo\RestApi\OpenApiDocFragmentJoiner;

/**
 * @covers Wikibase\Lexeme\Hooks\WikibaseRepoOpenApiDocFragmentsHookHandler
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class WikibaseRepoOpenApiDocFragmentsHookHandlerTest extends \MediaWikiIntegrationTestCase {

	public function testJoinsCommittedFragment(): void {
		$joinedFragment = null;
		$joiner = $this->createMock( OpenApiDocFragmentJoiner::class );
		$joiner->expects( $this->once() )
			->method( 'join' )
			->willReturnCallback( function ( array $fragment, string $sourceName ) use ( &$joinedFragment ) {
				$this->assertSame( 'WikibaseLexeme', $sourceName );
				$joinedFragment = $fragment;
			} );

		( new WikibaseRepoOpenApiDocFragmentsHookHandler() )
			->onWikibaseRepoOpenApiDocFragments( $joiner );

		$expectedPaths = array_map(
			static fn ( array $route ) => preg_replace( '/^\/wikibase/', '', $route['path'] ),
			RestRoutes::getAllRouteDefinitions(),
		);
		$this->assertEqualsCanonicalizing( $expectedPaths, array_keys( $joinedFragment['paths'] ) );
		// the joined fragment must be self-contained: nothing resolves $refs at runtime
		$this->assertStringNotContainsString( '"$ref"', json_encode( $joinedFragment ) );
	}

}
