<?php

namespace Wikibase\Lexeme\MediaWiki\Actions;

use Wikibase\Repo\Actions\ViewEntityAction;

/**
 * Handles the view action for Wikibase Lexeme.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class ViewLexemeAction extends ViewEntityAction {

	public function show() {
		parent::show();

		// Basic styles that should also be loaded if JavaScript is disabled
		$this->getOutput()->addModuleStyles( 'wikibase.lexeme.styles' );

		$this->getOutput()->addJsConfigVars( 'wbUserSpecifiedLanguages', [] );
		$this->getOutput()->addModules( 'wikibase.lexeme.lexemeview' );
	}

}
