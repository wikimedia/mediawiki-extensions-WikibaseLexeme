<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use Wikibase\Lexeme\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\Merge\LexemeMergeInteractor;
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
	 * @return LexemeMergeInteractor
	 */
	public static function getLexemeMergeInteractor() {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeMergeInteractor' );
	}

}
