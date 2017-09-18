<?php

namespace Wikibase\Lexeme\Api;

/**
 * @license GPL-2.0+
 */
class AddFormRequestParserResult {

	private $request;

	private $errors;

	/**
	 * @param AddFormRequest|null $request
	 * @param string[] $errors
	 */
	public function __construct( AddFormRequest $request = null, array $errors ) {
		$this->request = $request;
		$this->errors = $errors;
	}

	public static function newWithRequest( AddFormRequest $request ) {
		return new self( $request, [] );
	}

	public static function newWithErrors( array $errors ) {
		return new self( null, $errors );
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

	public function getErrors() {
		return $this->errors;
	}

}
