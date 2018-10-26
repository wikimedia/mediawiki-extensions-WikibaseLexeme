<?php

namespace Wikibase\Lexeme\Domain\Storage;

/**
 * @license GPL-2.0-or-later
 */
class GetLexemeException extends \RuntimeException {

	public function __construct( \Exception $previous = null ) {
		parent::__construct(
			'Could not retrieve Lexeme',
			0,
			$previous
		);
	}

}
