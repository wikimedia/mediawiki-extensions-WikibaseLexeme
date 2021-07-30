<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use IContextSource;
use MediaWiki\Permissions\PermissionManager;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;

/**
 * A factory for MediaWiki-specific {@link LexemeRepository} instances,
 * capturing MediaWiki-specific data that’s not part of the {@link LexemeRepository interface}.
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRepositoryFactory {

	/** @var EntityStore */
	private $entityStore;
	/** @var EntityRevisionLookup */
	private $entityRevisionLookup;
	/** @var PermissionManager */
	private $permissionManager;

	public function __construct(
		EntityStore $entityStore,
		EntityRevisionLookup $entityRevisionLookup,
		PermissionManager $permissionManager
	) {
		$this->entityStore = $entityStore;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->permissionManager = $permissionManager;
	}

	public function newFromContext(
		IContextSource $context,
		bool $botEditRequested = false,
		array $tags = []
	): LexemeRepository {
		return new MediaWikiLexemeRepository(
			$context->getUser(),
			$botEditRequested,
			$tags,
			$this->entityStore,
			$this->entityRevisionLookup,
			$this->permissionManager
		);
	}

}
