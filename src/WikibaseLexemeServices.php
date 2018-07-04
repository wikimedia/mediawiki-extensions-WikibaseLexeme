<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServices {

	/**
	 * @return ContentLanguages
	 */
	public static function getTermLanguages() {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeTermLanguages' );
	}

}
