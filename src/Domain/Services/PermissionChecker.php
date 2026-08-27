<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Services;

use Wikibase\Lexeme\Domain\Model\PermissionCheckResult;
use Wikibase\Lexeme\Domain\Model\User;

/**
 * @license GPL-2.0-or-later
 */
interface PermissionChecker {

	public function canCreateLexeme( User $user ): PermissionCheckResult;

}
