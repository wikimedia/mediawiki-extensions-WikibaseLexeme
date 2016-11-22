<?php

namespace Wikibase\Lexeme\Actions;

use Wikibase\ViewEntityAction;

/**
 * Handles the view action for Wikibase Lexeme.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class ViewLexemeAction extends ViewEntityAction {

	public function show() {
		parent::show();
		$this->getOutput()->addJsConfigVars( 'wbUserSpecifiedLanguages', [] );
		$this->getOutput()->addModules( 'wikibase.lexeme.lexemeview' );
	}

}
