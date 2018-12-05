<?php

namespace Wikibase\Lexeme\Domain;

use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeRedirector {

	/**
	 * @param LexemeId $sourceId
	 * @param LexemeId $targetId
	 *
	 * @return void
	 * @throws RedirectCreationFailed
	 */
	public function redirect( LexemeId $sourceId, LexemeId $targetId );

}
