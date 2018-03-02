<?php

namespace Wikibase\Lexeme\View;

use FormatJson;
use ResourceLoaderContext;
use ResourceLoaderFileModule;
use Wikibase\View\Template\TemplateRegistry;

/**
 * Injects templates into JavaScript.
 *
 * @license GPL-2.0-or-later
 */
class TemplateModule extends ResourceLoaderFileModule {

	/**
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$templates = include __DIR__ . '/../../resources/templates.php';
		$templateRegistry = new TemplateRegistry( $templates );

		$templatesJson = FormatJson::encode( $templateRegistry->getTemplates() );

		// template store JavaScript initialisation
		$script = <<<JS
( function( mw ) {
	'use strict';

	mw.wbTemplates.store.set( $.extend( $templatesJson, mw.wbTemplates.store.values ) );

}( mediaWiki ) );
JS;

		return $script . "\n" . parent::getScript( $context );
	}

	/**
	 * @see ResourceLoaderModule::supportsURLLoading
	 *
	 * @return bool
	 */
	public function supportsURLLoading() {
		return false; // always use getScript() to acquire JavaScript (even in debug mode)
	}

	/**
	 * @see ResourceLoaderModule::getDefinitionSummary
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return array
	 */
	public function getDefinitionSummary( ResourceLoaderContext $context ) {
		$summary = parent::getDefinitionSummary( $context );
		$summary['mtime'] = (string)filemtime( __DIR__ . '/../../resources/templates.php' );

		return $summary;
	}

}
