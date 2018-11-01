<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\Lexeme\Domain\Authorization\LexemeAuthorizer;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeAuthorizer implements LexemeAuthorizer {

	private $user;
	private $permissionChecker;

	/**
	 * @param \User $user
	 */
	public function __construct( \User $user, EntityPermissionChecker $permissionChecker ) {
		$this->user = $user;
		$this->permissionChecker = $permissionChecker;
	}

	public function canMerge( LexemeId $firstId, LexemeId $secondId ) {
		return $this->canMergeLexeme( $firstId ) && $this->canMergeLexeme( $secondId );
	}

	private function canMergeLexeme( LexemeId $id ) {
		$status = $this->permissionChecker->getPermissionStatusForEntityId(
			$this->user,
			EntityPermissionChecker::ACTION_MERGE,
			$id
		);

		return $status->isOK();
	}

}
