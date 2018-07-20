<?php

use MediaWiki\MediaWikiServices;
use Wikibase\Lexeme\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\Content\LexemeTermLanguages;
use Wikibase\Repo\WikibaseRepo;

// TODO Replace by framework-agnostic DI container.
// Pimple e.g. is well known in the free world and yet part of mediawiki-vendor
// Challenge: Dedicated API endpoints (e.g. AddForm) need to have it passed w/o singletons/globals
return [
	'WikibaseLexemeAdditionalLanguages' => function() {
		// TODO Problem when removing a code after such an item exists in DB
		return [ 'mis' ];
	},
	'WikibaseLexemeTermLanguages' => function( MediaWikiServices $mediawikiServices ) {
		return new LexemeTermLanguages(
			$mediawikiServices->getService( 'WikibaseLexemeAdditionalLanguages' )
		);
	},
	'WikibaseLexemeLanguageNameLookup' => function( MediaWikiServices $mediawikiServices ) {
		return new LexemeLanguageNameLookup(
			RequestContext::getMain(),
			$mediawikiServices->getService( 'WikibaseLexemeAdditionalLanguages' ),
			WikibaseRepo::getDefaultInstance()->getLanguageNameLookup()
		);
	}
];
