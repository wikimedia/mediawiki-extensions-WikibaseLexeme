<?php

namespace Wikibase\Lexeme\MediaWiki\Actions;

use Wikibase\Lexeme\WikibaseLexemeServices;
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

		if ( !WikibaseLexemeServices::getMobileView() ) { // T324991
			// Basic styles that should also be loaded if JavaScript is disabled
			$this->getOutput()->addModuleStyles( 'wikibase.lexeme.styles' );
			$this->getOutput()->addModules( 'wikibase.lexeme.lexemeview' );
		}

		$this->getOutput()->addJsConfigVars( 'wbUserSpecifiedLanguages', [] );
	}

}
