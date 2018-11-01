<?php

namespace Wikibase\Lexeme\Domain\Storage;

/**
 * @license GPL-2.0-or-later
 */
class UpdateLexemeException extends \RuntimeException {

	public function __construct( \Exception $previous = null ) {
		parent::__construct(
			'Could not update Lexeme',
			0,
			$previous
		);
	}

}
