<?php
declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Specials;

use SpecialPage;

/**
 * New page for creating new Lexeme entities.
 *
 * @license GPL-2.0-or-later
 */
class SpecialNewLexemeAlpha extends SpecialPage {

	public function __construct() {
		parent::__construct(
			'NewLexemeAlpha',
			// We might want to temporarily restrict this page even further,
			// pending product decision.
			'createpage',
			// Unlist this page from Special:SpecialPages.
			false
		);
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ): void {

		$output = $this->getOutput();
		$this->setHeaders();

		$output->addHTML( '<div id="special-newlexeme-root"></div>' );
		$output->addModules( [ 'wikibase.lexeme.special.NewLexemeAlpha' ] );
	}

	public function setHeaders() {
		$out = $this->getOutput();
		$out->setPageTitle( $this->getDescription() );
	}

	public function getDescription() {
		return $this->msg( 'special-newlexeme-alpha' )->text();
	}
}
