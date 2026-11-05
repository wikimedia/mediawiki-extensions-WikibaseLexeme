<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Services;

use InvalidArgumentException;
use Wikibase\Lexeme\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Lexeme\Domain\Services\Exceptions\PatchTestConditionFailedException;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeJsonPatcher {

	/**
	 * @return mixed usually an array, but any JSON value if the patch replaces the root element
	 *
	 * @throws InvalidArgumentException for an invalid patch
	 * @throws PatchPathException if a path target provided in the patch does not exist
	 * @throws PatchTestConditionFailedException if a "test" op in the patch fails
	 */
	public function patch( array $target, array $patch ): mixed;

}
