<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use MediaWiki\Permissions\PermissionManager;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Storage\GetLexemeException;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lexeme\Domain\Storage\UpdateLexemeException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Content\EntityContent;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRepository implements LexemeRepository {

	private $user;
	private $botEditRequested;
	private $tags;
	private $entityStore;
	private $entityRevisionLookup;
	private $permissionManager;

	/**
	 * @param \User $user
	 * @param bool $botEditRequested Whether the user has requested that edits be marked as bot edits.
	 * Ignored if the user does not have the 'bot' right.
	 * @param EntityStore $entityStore Needs to be able to save Lexeme entities
	 * @param EntityRevisionLookup $entityRevisionLookup Needs to be able to retrieve Lexeme entities
	 * @param PermissionManager $permissionManager
	 */
	public function __construct(
		\User $user,
		bool $botEditRequested,
		array $tags,
		EntityStore $entityStore,
		EntityRevisionLookup $entityRevisionLookup,
		PermissionManager $permissionManager
	) {

		$this->user = $user;
		$this->botEditRequested = $botEditRequested;
		$this->tags = $tags;
		$this->entityStore = $entityStore;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->permissionManager = $permissionManager;
	}

	public function updateLexeme( Lexeme $lexeme, string $editSummary ) {
		// TODO: assert id not null

		try {
			return $this->entityStore->saveEntity(
				$lexeme,
				$editSummary,
				$this->user,
				$this->getSaveFlags(),
				false,
				$this->tags
			);
		} catch ( StorageException $ex ) {
			throw new UpdateLexemeException( $ex );
		}
	}

	private function getSaveFlags() {
		// TODO: the EntityContent::EDIT_IGNORE_CONSTRAINTS flag does not seem to be used by Lexeme
		// (LexemeHandler has no onSaveValidators)
		$flags = EDIT_UPDATE | EntityContent::EDIT_IGNORE_CONSTRAINTS;

		if ( $this->botEditRequested && $this->permissionManager->userHasRight( $this->user, 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		return $flags;
	}

	/**
	 * @param LexemeId $id
	 *
	 * @return Lexeme|null
	 * @throws GetLexemeException
	 */
	public function getLexemeById( LexemeId $id ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision(
				$id,
				0,
				LookupConstants::LATEST_FROM_MASTER
			);

			if ( $revision ) {
				// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
				return $revision->getEntity();
			}

			return null;
		} catch ( StorageException | RevisionedUnresolvedRedirectException $ex ) {
			throw new GetLexemeException( $ex );
		}
	}

}
