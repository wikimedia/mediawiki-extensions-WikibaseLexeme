<?php

use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\WikibaseLexemeServices;
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
	Form::ENTITY_TYPE => [
		ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => static function (): FallbackEntitySearchHelperController {
			return new FallbackEntitySearchHelperController(
				Form::ENTITY_TYPE,
				WikibaseLexemeServices::getFormSearchHelper(),
				WikibaseRepo::getEntitySourceLookup(),
			);
		},
	],
	Lexeme::ENTITY_TYPE => [
		ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => static function (): FallbackEntitySearchHelperController {
			return new FallbackEntitySearchHelperController(
				Lexeme::ENTITY_TYPE,
				WikibaseLexemeServices::getLexemeSearchHelper(),
				WikibaseRepo::getEntitySourceLookup(),
			);
		},
	],
];
