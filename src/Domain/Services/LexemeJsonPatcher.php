<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Services;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeJsonPatcher {

	/**
	 * @return mixed usually an array, but any JSON value if the patch replaces the root element
	 */
	public function patch( array $target, array $patch ): mixed;

}
