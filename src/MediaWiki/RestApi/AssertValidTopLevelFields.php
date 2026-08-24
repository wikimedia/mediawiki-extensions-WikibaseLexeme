<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use InvalidArgumentException;
use MediaWiki\Rest\HttpException;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikimedia\Assert\Assert;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
trait AssertValidTopLevelFields {

	/**
	 * @throws HttpException
	 */
	public function assertValidTopLevelTypes( ?array $body, array $paramSettings ): void {
		foreach ( $paramSettings as $fieldName => $fieldSettings ) {
			if ( isset( $body[$fieldName] ) ) {
				$this->assertType( $fieldSettings[ParamValidator::PARAM_TYPE], $fieldName, $body[$fieldName] );
			} elseif ( $fieldSettings[ParamValidator::PARAM_REQUIRED] === true ) {
				throw ( new ResponseFactory() )->newHttpExceptionFromError(
					UseCaseError::newMissingField( '', $fieldName )
				);
			}
		}
	}

	/**
	 * @throws HttpException
	 */
	private function assertType( string $type, string $fieldName, mixed $value ): void {
		try {
			Assert::parameterType( $type, $value, '$field' );
		} catch ( InvalidArgumentException ) {
			throw ( new ResponseFactory() )->newHttpExceptionFromError(
				UseCaseError::newInvalidValue( "/$fieldName" )
			);
		}
	}

}
