<?php

namespace Wikibase\Lexeme\Domain\Authorization;

use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeAuthorizer {

	/**
	 * Should return false on infrastructure failure.
	 * @return bool
	 */
	public function canMerge( LexemeId $firstId, LexemeId $secondId );

}
