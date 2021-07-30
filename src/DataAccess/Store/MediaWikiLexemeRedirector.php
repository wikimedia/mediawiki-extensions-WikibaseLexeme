<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use IContextSource;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
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

	/** @var bool */
	private $botEditRequested;

	/** @var string[] */
	private $tags;

	/** @var IContextSource */
	private $context;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		SummaryFormatter $summaryFormatter,
		IContextSource $context,
		EditFilterHookRunner $editFilterHookRunner,
		EntityRedirectTargetLookup $entityRedirectLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		bool $botEditRequested,
		array $tags
	) {
		$this->botEditRequested = $botEditRequested;
		$this->tags = $tags;
		$this->context = $context;

		parent::__construct(
			$entityRevisionLookup,
			$entityStore,
			$permissionChecker,
			$summaryFormatter,
			$editFilterHookRunner,
			$entityRedirectLookup,
			$entityTitleLookup
		);
	}

	public function redirect( LexemeId $sourceId, LexemeId $targetId ) {
		$this->createRedirect( $sourceId, $targetId, $this->botEditRequested, $this->tags, $this->context );
	}

	protected function assertEntityIsRedirectable( EntityDocument $entity ) {
		// as of now, all kinds of lexemes can be redirected
	}

}
