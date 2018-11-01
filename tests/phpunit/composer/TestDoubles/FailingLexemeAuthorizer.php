<?php

namespace Wikibase\Lexeme\Tests\TestDoubles;

use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class FailingLexemeAuthorizer implements LexemeAuthorizer {

	public function canMerge( LexemeId $firstId, LexemeId $secondId ) {
		return false;
	}

}
