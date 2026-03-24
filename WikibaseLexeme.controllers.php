<?php

use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\ControllerRegistry;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\FallbackEntitySearchHelperController;
use Wikibase\Repo\WikibaseRepo;

/**
 * Controller callback definitions for Lexeme entity types.
 *
 * @note Avoid instantiating objects here! Use callbacks (closures) instead.
 *
 * @license GPL-2.0-or-later
 */

return [
	Lexeme::ENTITY_TYPE => [
		ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => static function (): FallbackEntitySearchHelperController {
			// This just serves as an example.
			// The fallback implementation should no longer be used once T420683 is done.
			return new FallbackEntitySearchHelperController(
				Lexeme::ENTITY_TYPE,
				WikibaseRepo::getEntitySearchHelper(),
				WikibaseRepo::getEntitySourceLookup(),
			);
		},
	],
];
