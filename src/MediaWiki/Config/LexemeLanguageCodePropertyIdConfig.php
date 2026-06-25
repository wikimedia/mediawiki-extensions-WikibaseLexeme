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

	/**
	 * Include the LexemeLanguageCodePropertyId in the definition summary to ensure that changes
	 * to the configuration are sent to the frontend and a stale configuration is not kept
	 * in the ResourceLoader cache.
	 *
	 * @inheritDoc
	 */
	public function getDefinitionSummary( RL\Context $context ) {
		$summary = parent::getDefinitionSummary( $context );
		$summary[] = [
			'LexemeLanguageCodePropertyId' => $this->getConfig()->get( 'LexemeLanguageCodePropertyId' ),
		];
		return $summary;
	}
}
