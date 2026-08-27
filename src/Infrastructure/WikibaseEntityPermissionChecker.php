<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Infrastructure;

use MediaWiki\Block\Block;
use MediaWiki\User\UserFactory;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\PermissionCheckResult;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\PermissionChecker;
use Wikibase\Repo\Store\EntityPermissionChecker;

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
		$status = $this->entityPermissionChecker->getPermissionStatusForEntity(
			$user->isAnonymous() ?
				$this->userFactory->newAnonymous() :
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable isAnonymous checks for null
				$this->userFactory->newFromName( $user->getUsername() ),
			EntityPermissionChecker::ACTION_EDIT,
			new Lexeme()
		);

		if ( $status->isGood() ) {
			return PermissionCheckResult::ALLOWED;
		}

		return match ( $status->getBlock()?->getTarget()->getType() ) {
			Block::TYPE_USER => PermissionCheckResult::USER_BLOCKED,
			Block::TYPE_IP,
			Block::TYPE_RANGE,
			Block::TYPE_AUTO => PermissionCheckResult::IP_BLOCKED,
			default => PermissionCheckResult::DENIED_UNKNOWN_REASON,
		};
	}

}
