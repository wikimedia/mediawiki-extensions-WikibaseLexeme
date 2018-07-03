<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServices {

	/* @return \Wikibase\Lib\ContentLanguages */
	public static function getTermLanguages() {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeTermLanguages' );
	}

}
