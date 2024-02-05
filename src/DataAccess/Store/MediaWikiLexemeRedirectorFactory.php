<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use IContextSource;
use MediaWiki\User\TempUser\TempUserCreator;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * A factory for {@link MediaWikiLexemeRedirector} instances,
 * capturing MediaWiki-specific data that’s not part of the LexemeRedirector interface.
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRedirectorFactory {

	/** @var EntityRevisionLookup */
	private $entityRevisionLookup;
	/** @var EntityStore */
	private $entityStore;
	/** @var EntityPermissionChecker */
	private $permissionChecker;
	/** @var SummaryFormatter */
	private $summaryFormatter;
	/** @var EditFilterHookRunner */
	private $editFilterHookRunner;
	/** @var EntityRedirectTargetLookup */
	private $entityRedirectLookup;
	/** @var EntityTitleStoreLookup */
	private $entityTitleLookup;
	private TempUserCreator $tempUserCreator;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		SummaryFormatter $summaryFormatter,
		EditFilterHookRunner $editFilterHookRunner,
		EntityRedirectTargetLookup $entityRedirectLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		TempUserCreator $tempUserCreator
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->summaryFormatter = $summaryFormatter;
		$this->editFilterHookRunner = $editFilterHookRunner;
		$this->entityRedirectLookup = $entityRedirectLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->tempUserCreator = $tempUserCreator;
	}

	/**
	 * @param string[] $tags
	 */
	public function newFromContext(
		IContextSource $context,
		bool $botEditRequested,
		array $tags
	): MediaWikiLexemeRedirector {
		return new MediaWikiLexemeRedirector(
			$this->entityRevisionLookup,
			$this->entityStore,
			$this->permissionChecker,
			$this->summaryFormatter,
			$context,
			$this->editFilterHookRunner,
			$this->entityRedirectLookup,
			$this->entityTitleLookup,
			$this->tempUserCreator,
			$botEditRequested,
			$tags
		);
	}

}
