<?php

namespace Wikibase\Lexeme\Presentation\View;

use FormatJson;
// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use Wikibase\View\Template\TemplateRegistry;

/**
 * Injects templates into JavaScript.
 *
 * @license GPL-2.0-or-later
 */
class TemplateModule {

	/**
	 * Get templates.php as a JavaScript function call
	 *
	 * @param RL\Context $context
	 *
	 * @return string
	 */
	public static function getScript( RL\Context $context ) {
		$templates = include __DIR__ . '/../../../resources/templates.php';
		$templateRegistry = new TemplateRegistry( $templates );

		$templatesJson = FormatJson::encode( $templateRegistry->getTemplates() );

		// template store JavaScript initialisation
		return <<<JS
( function () {
	'use strict';

	mw.wbTemplates.store.set( $.extend( $templatesJson, mw.wbTemplates.store.values ) );

}() );
JS;
	}

	/**
	 * Get the version corresponding to getScript()
	 *
	 * @param RL\Context $context
	 * @return RL\FilePath
	 */
	public static function getVersion( RL\Context $context ) {
		return new RL\FilePath( 'templates.php' );
	}

}
