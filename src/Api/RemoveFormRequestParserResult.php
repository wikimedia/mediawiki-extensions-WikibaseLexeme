<?php

namespace Wikibase\Lexeme\Api;

use LogicException;
use Status;
use Wikibase\Lexeme\Api\Error\ApiError;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormRequestParserResult {

	/**
	 * @var RemoveFormRequest|null
	 */
	private $request;

	/**
	 * @var ApiError[]
	 */
	private $errors;

	public static function newWithRequest( RemoveFormRequest $request ) {
		return new self( $request, [] );
	}

	/**
	 * @param ApiError[] $errors
	 * @return self
	 */
	public static function newWithErrors( array $errors ) {
		return new self( null, $errors );
	}

	/**
	 * @param RemoveFormRequest|null $request
	 * @param ApiError[] $errors
	 */
	private function __construct( RemoveFormRequest $request = null, array $errors ) {
		Assert::parameterElementType( ApiError::class, $errors, '$errors' );
		$this->request = $request;
		$this->errors = $errors;
	}

	/**
	 * @return RemoveFormRequest
	 */
	public function getRequest() {
		if ( $this->errors ) {
			throw new LogicException(
				'There have been errors when parsing the request. Call asFatalStatus to handle them'
			);
		}

		return $this->request;
	}

	public function hasErrors() {
		return !empty( $this->errors );
	}

	/**
	 * @return Status
	 */
	public function asFatalStatus() {
		if ( !$this->hasErrors() ) {
			throw new LogicException( 'Successful result can not be converted to fatal status' );
		}

		$status = Status::newGood();
		foreach ( $this->errors as $error ) {
			$status->fatal( $error->asApiMessage() );
		}
		return $status;
	}

}
