<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\GetLexeme;

use RuntimeException;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class LexemeRedirect extends RuntimeException {

	public function __construct( public readonly LexemeId $redirectTarget ) {
		parent::__construct();
	}

}
