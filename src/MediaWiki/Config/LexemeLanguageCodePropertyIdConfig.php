<?php

namespace Wikibase\Lexeme\MediaWiki\Config;

// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLanguageCodePropertyIdConfig extends RL\Module {

	/**
	 * Used to propagate the ISO 639-2 code property ID to JavaScript.
	 *
	 * @see RL\Module::getScript
	 *
	 * @param RL\Context $context
	 *
	 * @return string
	 */
	public function getScript( RL\Context $context ) {
		return ResourceLoader::makeConfigSetScript( [
			'LexemeLanguageCodePropertyId' => $this->getConfig()->get( 'LexemeLanguageCodePropertyId' ),
		] );
	}

	public function getTargets() {
		return [ 'desktop', 'mobile' ];
	}

}
