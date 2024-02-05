<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\MergeLexemes;

use IContextSource;
use MediaWiki\User\UserIdentity;
use Wikibase\Repo\TempUserStatus;

/**
 * @license GPL-2.0-or-later
 */
class MergeLexemesStatus extends TempUserStatus {

	public static function newMerge(
		// no custom data at the moment
		?UserIdentity $savedTempUser,
		IContextSource $context
	): self {
		return self::newTempUserStatus( [], $savedTempUser, $context );
	}

}
