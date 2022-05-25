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
class TemplateModule extends RL\FileModule {

	/**
	 * @see RL\Module::getScript
	 *
	 * @param RL\Context $context
	 *
	 * @return string
	 */
	public function getScript( RL\Context $context ) {
		$templates = include __DIR__ . '/../../../resources/templates.php';
		$templateRegistry = new TemplateRegistry( $templates );

		$templatesJson = FormatJson::encode( $templateRegistry->getTemplates() );

		// template store JavaScript initialisation
		$script = <<<JS
( function () {
	'use strict';

	mw.wbTemplates.store.set( $.extend( $templatesJson, mw.wbTemplates.store.values ) );

}() );
JS;

		return $script . "\n" . parent::getScript( $context );
	}

	/**
	 * @see RL\Module::supportsURLLoading
	 *
	 * @return bool
	 */
	public function supportsURLLoading() {
		return false; // always use getScript() to acquire JavaScript (even in debug mode)
	}

	/**
	 * @see RL\Module::getDefinitionSummary
	 *
	 * @param RL\Context $context
	 *
	 * @return array
	 */
	public function getDefinitionSummary( RL\Context $context ) {
		$summary = parent::getDefinitionSummary( $context );
		$summary['mtime'] = (string)filemtime( __DIR__ . '/../../../resources/templates.php' );

		return $summary;
	}

}
