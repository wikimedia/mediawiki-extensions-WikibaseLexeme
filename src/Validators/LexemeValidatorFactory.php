<?php

namespace Wikibase\Lexeme\Validators;

use ValueValidators\ValueValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\TypeValidator;
use Wikimedia\Assert\Assert;

/**
 * Provides validators that can be used to validates elements of the Lexeme entity.
 *
 * @license GPL-2.0+
 */
class LexemeValidatorFactory {

	/**
	 * @var int
	 */
	private $maxTermLength;

	/**
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	/**
	 * @param int $maxTermLength max string length for lemma term
	 * @param TermValidatorFactory $termValidatorFactory
	 */
	public function __construct( $maxTermLength, TermValidatorFactory $termValidatorFactory ) {
		Assert::parameterType( 'integer', $maxTermLength, '$maxLength' );

		$this->maxTermLength = $maxTermLength;
		$this->termValidatorFactory = $termValidatorFactory;
	}

	/**
	 * @return ValueValidator
	 */
	public function getLemmaTermValidator() {
		// TODO: validate UTF8
		return new CompositeValidator(
			[
				new TypeValidator( 'string' ),
				new StringLengthValidator( 1, $this->maxTermLength, 'mb_strlen' ),
				new RegexValidator( '/^\s|\v|\s$/', true ),
			],
			true
		);
	}

	/**
	 * @return ValueValidator
	 */
	public function getLanguageCodeValidator() {
		return $this->termValidatorFactory->getLanguageValidator();
	}

}
