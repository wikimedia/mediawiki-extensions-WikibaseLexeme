<?php

use Wikibase\Lexeme\Content\LexemeTermLanguages;

// TODO Replace by framework-agnostic DI container.
// Pimple e.g. is well known in the free world and yet part of mediawiki-vendor
// Challenge: Dedicated API endpoints (e.g. AddForm) need to have it passed w/o singletons/globals
return [
	'WikibaseLexemeTermLanguages' => function() {
		// TODO List from config? Problem when removing a code after such an item exists in DB
		return new LexemeTermLanguages( [ 'mis' ] );
	}
];
