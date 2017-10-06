<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\Api\Error\ApiError;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class AddFormRequestParserResult {

	/**
	 * @var AddFormRequest|null
	 */
	private $request;

	/**
	 * @var ApiError[]
	 */
	private $errors;

	public static function newWithRequest( AddFormRequest $request ) {
		return new self( $request, [] );
	}

	/**
	 * @param ApiError[] $errors
	 * @return AddFormRequestParserResult
	 */
	public static function newWithErrors( array $errors ) {
		return new self( null, $errors );
	}

	/**
	 * @param AddFormRequest|null $request
	 * @param ApiError[] $errors
	 */
	private function __construct( AddFormRequest $request = null, array $errors ) {
		Assert::parameterElementType( ApiError::class, $errors, '$errors' );
		$this->request = $request;
		$this->errors = $errors;
	}

	public function getRequest() {
		if ( $this->errors ) {
			throw new \LogicException(
				'There have been errors when parsing the request. Call getErrors to handle them'
			);
		}

		return $this->request;
	}

	public function hasErrors() {
		return !empty( $this->errors );
	}

	/**
	 * @return \Status
	 */
	public function asFatalStatus() {
		if ( !$this->hasErrors() ) {
			throw new \LogicException( 'Succesful result can not be converted to fatal status' );
		}

		$status = \Status::newGood();
		foreach ( $this->errors as $error ) {
			$status->fatal( $error->asApiMessage() );
		}
		return $status;
	}

}
