<?php

namespace Wikibase\Lexeme\MediaWiki\Config;

// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLanguageCodePropertyIdConfig extends RL\Module {

	/**
	 * Propagate the ISO 639-2 code property ID from PHP to JavaScript.
	 *
	 * @see RL\Module::getScript
	 * @param RL\Context $context
	 * @return string
	 */
	public function getScript( RL\Context $context ) {
		return 'mw.config.set(' . $context->encodeJson( [
			'LexemeLanguageCodePropertyId' => $this->getConfig()->get( 'LexemeLanguageCodePropertyId' ),
		] ) . ');';
	}

}
