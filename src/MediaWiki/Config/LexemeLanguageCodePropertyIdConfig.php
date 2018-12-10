<?php

namespace Wikibase\Lexeme\MediaWiki\Config;

use MediaWiki\MediaWikiServices;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLanguageCodePropertyIdConfig extends ResourceLoaderModule {

	/**
	 * Used to propagate the ISO 639-2 code property ID to JavaScript.
	 *
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		return 'mw.config.set( "LexemeLanguageCodePropertyId", '
			. json_encode( $config->get( 'LexemeLanguageCodePropertyId' ) )
			. ' );';
	}

}
