<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use Wikibase\Repo\Hooks\WikibaseRepoOpenApiDocFragmentsHook;
use Wikibase\Repo\RestApi\OpenApiDocFragmentJoiner;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoOpenApiDocFragmentsHookHandler implements WikibaseRepoOpenApiDocFragmentsHook {

	private const FRAGMENT_FILE = __DIR__ . '/../MediaWiki/RestApi/specs/openapi.fragment.dereferenced.json';

	public function onWikibaseRepoOpenApiDocFragments( OpenApiDocFragmentJoiner $joiner ): void {
		// Of the fragment's paths, Wikibase joins only those the wiki's REST
		// router can actually route, so joining is safe even while our routes
		// are dev-only and not registered on every wiki.
		$joiner->join(
			json_decode( file_get_contents( self::FRAGMENT_FILE ), true ),
			'WikibaseLexeme'
		);
	}

}
