<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Infrastructure;

use MediaWiki\Block\Block;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\User\User as MediaWikiUser;
use MediaWiki\User\UserFactory;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\PermissionCheckResult;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\PermissionChecker;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Message\MessageSpecifier;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityPermissionChecker implements PermissionChecker {

	public function __construct(
		private readonly EntityPermissionChecker $entityPermissionChecker,
		private readonly UserFactory $userFactory,
	) {
	}

	public function canCreateLexeme( User $user ): PermissionCheckResult {
		return $this->newPermissionCheckResultFromStatus(
			$this->entityPermissionChecker->getPermissionStatusForEntity(
				$this->getMediaWikiUser( $user ),
				EntityPermissionChecker::ACTION_EDIT,
				new Lexeme()
			)
		);
	}

	public function canEditLexeme( User $user, LexemeId $id ): PermissionCheckResult {
		return $this->newPermissionCheckResultFromStatus(
			$this->entityPermissionChecker->getPermissionStatusForEntityId(
				$this->getMediaWikiUser( $user ),
				EntityPermissionChecker::ACTION_EDIT,
				$id
			)
		);
	}

	private function getMediaWikiUser( User $user ): MediaWikiUser {
		return $user->isAnonymous() ?
			$this->userFactory->newAnonymous() :
			// isAnonymous checks for null
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable,PhanTypeMismatchReturnNullable
			$this->userFactory->newFromName( $user->getUsername() );
	}

	private function newPermissionCheckResultFromStatus( PermissionStatus $status ): PermissionCheckResult {
		if ( $status->isGood() ) {
			return PermissionCheckResult::ALLOWED;
		} elseif ( $this->hasError( 'protectedpagetext', $status ) ) {
			return PermissionCheckResult::PAGE_PROTECTED;
		}
		return match ( $status->getBlock()?->getTarget()->getType() ) {
			Block::TYPE_USER => PermissionCheckResult::USER_BLOCKED,
			Block::TYPE_IP,
			Block::TYPE_RANGE,
			Block::TYPE_AUTO => PermissionCheckResult::IP_BLOCKED,
			default => PermissionCheckResult::DENIED_UNKNOWN_REASON
		};
	}

	private function hasError( string $error, PermissionStatus $status ): bool {
		return in_array(
			$error,
			array_map(
				static fn ( MessageSpecifier $message ) => $message->getKey(),
				$status->getMessages()
			)
		);
	}

}
