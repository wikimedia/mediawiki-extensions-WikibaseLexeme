<?php

namespace Wikibase\Lexeme\Config;

use MediaWiki\MediaWikiServices;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * @license GPL-2.0+
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

		return 'mediaWiki.config.set( "LexemeLanguageCodePropertyId", '
			. json_encode( $config->get( 'LexemeLanguageCodePropertyId' ) )
			. ' );';
	}

}
