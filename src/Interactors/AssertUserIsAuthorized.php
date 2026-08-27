<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors;

use Wikibase\Lexeme\Domain\Model\PermissionCheckResult;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\PermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class AssertUserIsAuthorized {

	public function __construct( private readonly PermissionChecker $permissionChecker ) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function checkCreateLexemePermissions( User $user ): void {
		$result = $this->permissionChecker->canCreateLexeme( $user );
		if ( $result === PermissionCheckResult::ALLOWED ) {
			return;
		}

		throw match ( $result ) {
			PermissionCheckResult::USER_BLOCKED => UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED
			),
			PermissionCheckResult::IP_BLOCKED => UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_IP_BLOCKED
			),
			PermissionCheckResult::DENIED_UNKNOWN_REASON => new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				'You have no permission to create a lexeme'
			),
		};
	}

}
