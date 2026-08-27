<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Interactors;

use Wikibase\Lexeme\Domain\Model\Exceptions\RateLimitReached;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;

/**
 * @license GPL-2.0-or-later
 */
trait UpdateExceptionHandler {

	/**
	 * @throws UseCaseError
	 */
	public function executeWithExceptionHandling( callable $callback ): mixed {
		try {
			return $callback();
		} catch ( TempAccountCreationLimitReached ) {
			throw UseCaseError::newRateLimitReached( UseCaseError::REQUEST_LIMIT_REASON_TEMP_ACCOUNT_CREATION_LIMIT );
		} catch ( ResourceTooLargeException $e ) {
			throw UseCaseError::newResourceTooLarge( $e->resourceSizeLimit );
		} catch ( RateLimitReached ) {
			throw UseCaseError::newRateLimitReached( UseCaseError::REQUEST_LIMIT_REASON_RATE_LIMIT );
		}
	}

}
