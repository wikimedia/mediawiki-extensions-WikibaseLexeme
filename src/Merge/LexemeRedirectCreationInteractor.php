<?php

namespace Wikibase\Lexeme\Merge;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\Interactors\EntityRedirectCreationInteractor;

/**
 * @license GPL-2.0-or-later
 */
class LexemeRedirectCreationInteractor extends EntityRedirectCreationInteractor {

	/**
	 * @param EntityDocument $entity
	 */
	protected function assertEntityIsRedirectable( EntityDocument $entity ) {
		// as of now, all kinds of lexemes can be redirected
	}

}
