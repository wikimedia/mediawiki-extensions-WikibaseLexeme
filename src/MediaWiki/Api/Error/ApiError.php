<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;

/**
 * @license GPL-2.0-or-later
 */
interface ApiError {

	public function asApiMessage( string $parameterName, array $path ): ApiMessage;

}
