<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp\Validation;

use ValueValidators\Result;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\TypeValidator;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermValueValidator {

	public const MAX_LENGTH = 1000;

	/**
	 * @var CompositeValidator
	 */
	private $validator;

	/**
	 * @param int $maxTermLength MAX_LENGTH
	 */
	public function __construct( $maxTermLength ) {
		// TODO: validate UTF8
		$this->validator = new CompositeValidator(
			[
				new TypeValidator( 'string' ),
				new StringLengthValidator( 1, $maxTermLength, 'mb_strlen' ),
				new RegexValidator( '/^\s|[\v\t]|\s$/u', true ),
			],
			true
		);
	}

	/**
	 * @param mixed $value
	 * @return Result
	 */
	public function validate( $value ) {
		return $this->validator->validate( $value );
	}

}
