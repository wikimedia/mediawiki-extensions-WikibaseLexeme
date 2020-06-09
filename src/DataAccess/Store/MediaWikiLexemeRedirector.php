<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\Lexeme\Domain\LexemeRedirector;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\Interactors\EntityRedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRedirector extends EntityRedirectCreationInteractor
	implements LexemeRedirector {

	private $botEditRequested;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		SummaryFormatter $summaryFormatter,
		User $user,
		EditFilterHookRunner $editFilterHookRunner,
		EntityRedirectLookup $entityRedirectLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		$botEditRequested
	) {
		$this->botEditRequested = $botEditRequested;

		parent::__construct(
			$entityRevisionLookup,
			$entityStore,
			$permissionChecker,
			$summaryFormatter,
			$user,
			$editFilterHookRunner,
			$entityRedirectLookup,
			$entityTitleLookup
		);
	}

	public function redirect( LexemeId $sourceId, LexemeId $targetId ) {
		$this->createRedirect( $sourceId, $targetId, $this->botEditRequested );
	}

	protected function assertEntityIsRedirectable( EntityDocument $entity ) {
		// as of now, all kinds of lexemes can be redirected
	}

}
