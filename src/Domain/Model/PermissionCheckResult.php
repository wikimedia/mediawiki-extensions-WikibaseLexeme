<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
enum PermissionCheckResult {

	case ALLOWED;
	case USER_BLOCKED;
	case IP_BLOCKED;
	case DENIED_UNKNOWN_REASON;

}
