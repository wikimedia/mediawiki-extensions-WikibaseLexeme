<?php

namespace Wikibase\Lexeme\Domain;

/**
 * @license GPL-2.0-or-later
 */
class RedirectCreationFailed extends \RuntimeException {

	public function __construct( \Exception $previous = null ) {
		parent::__construct(
			'Could not redirect Lexemes',
			0,
			$previous
		);
	}

}
