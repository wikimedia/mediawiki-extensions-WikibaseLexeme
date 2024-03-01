<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\Interactors\EntityRedirectCreationInteractor;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRedirector extends EntityRedirectCreationInteractor {

	protected function assertEntityIsRedirectable( EntityDocument $entity ) {
		// as of now, all kinds of lexemes can be redirected
	}

}
