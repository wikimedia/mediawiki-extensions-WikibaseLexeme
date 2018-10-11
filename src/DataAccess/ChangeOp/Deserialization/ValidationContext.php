<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp\Deserialization;

use ApiUsageException;
use Status;
use Wikibase\Lexeme\MediaWiki\Api\Error\ApiError;

/**
 * @license GPL-2.0-or-later
 */
class ValidationContext {

	private $field = '';

	private $level = '';

	/**
	 * @var self
	 */
	private $parentContext;

	/**
	 * @var ApiError[]
	 */
	private $violations = [];

	private $throwOnFirstViolation = true;

	private function __construct( self $parentContext = null, $field, $level = null ) {
		$this->parentContext = $parentContext;
		$this->field = $field;
		if ( $level !== null ) {
			$this->level = $level;
		}
	}

	public static function create( $field ) {
		return new self( null, $field );
	}

	/**
	 * Start a new level
	 *
	 * @param string $level
	 * @return self
	 */
	public function at( $level ) {
		return new self( $this, $this->field, $level );
	}

	public function addViolation( ApiError $error ) {
		$this->violations[] = $error;

		if ( $this->throwOnFirstViolation ) {
			$this->toApiUsageException();
		}
	}

	private function toApiUsageException() {
		foreach ( $this->violations as $violation ) {
			/** @var ApiError $error */
			$msg = $violation->asApiMessage( $this->field, $this->getParts() );
			$msg->setApiData( [ 'parameterName' => $this->field, 'fieldPath' => $this->getParts() ] );

			$status = Status::newGood();
			$status->fatal( $msg );

			throw new ApiUsageException( null, $status );
		}
	}

	private function getParts() {
		if ( $this->parentContext === null ) {
			return [];
		}
		return array_merge(
			$this->parentContext->getParts(),
			[ $this->level ]
		);
	}

}
