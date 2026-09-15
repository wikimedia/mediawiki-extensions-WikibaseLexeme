<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors;

use Wikibase\Lexeme\Domain\Model\LexemeId;
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
	public function checkEditPermissions( User $user, LexemeId $id ): void {
		$result = $this->permissionChecker->canEditLexeme( $user, $id );
		if ( $result === PermissionCheckResult::ALLOWED ) {
			return;
		}

		$this->throwUseCaseError( $result, 'You have no permission to edit this resource' );
	}

	/**
	 * @throws UseCaseError
	 */
	public function checkCreateLexemePermissions( User $user ): void {
		$result = $this->permissionChecker->canCreateLexeme( $user );
		if ( $result === PermissionCheckResult::ALLOWED ) {
			return;
		}

		$this->throwUseCaseError( $result, 'You have no permission to create a lexeme' );
	}

	private function throwUseCaseError( PermissionCheckResult $result, string $defaultMessage ): never {
		throw match ( $result ) {
			PermissionCheckResult::PAGE_PROTECTED => UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_PAGE_PROTECTED
			),
			PermissionCheckResult::USER_BLOCKED => UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED
			),
			PermissionCheckResult::IP_BLOCKED => UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_IP_BLOCKED
			),
			default => new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				$defaultMessage
			)
		};
	}

}
