<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\MediaWiki\Content\LexemeTermLanguages;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeServices {

	public static function getTermLanguages() : LexemeTermLanguages {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeTermLanguages' );
	}

	public static function getLanguageNameLookup() : LexemeLanguageNameLookup {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeLanguageNameLookup' );
	}

	public static function getLexemeMergeInteractor() : MergeLexemesInteractor {
		return MediaWikiServices::getInstance()->getService( 'WikibaseLexemeMergeInteractor' );
	}

	public static function getEditFormChangeOpDeserializer() : EditFormChangeOpDeserializer {
		return MediaWikiServices::getInstance()->getService(
			'WikibaseLexemeEditFormChangeOpDeserializer'
		);
	}

}
