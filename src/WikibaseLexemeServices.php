<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use Wikibase\Lexeme\Content\LexemeLanguageNameLookup;
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

	/**
	 * @return LexemeLanguageNameLookup
	 */
	public static function getLanguageNameLookup() {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeLanguageNameLookup' );
	}

	/**
	 * @return array
	 */
	public static function getAdditionalLanguages() {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeAdditionalLanguages' );
	}

}
