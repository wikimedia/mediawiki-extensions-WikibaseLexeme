<?php

namespace Wikibase\Lexeme;

use ValueValidators\ValueValidator;
use Wikibase\Lexeme\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikimedia\Assert\Assert;

/**
 * Provides validators that can be used to validates elements of the Lexeme entity.
 *
 * @license GPL-2.0-or-later
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
	 * @var ValueValidator[]
	 */
	private $itemValidators;

	/**
	 * @param int $maxTermLength max string length for lemma term
	 * @param TermValidatorFactory $termValidatorFactory
	 * @param ValueValidator[] $itemValidators
	 */
	public function __construct(
		$maxTermLength,
		TermValidatorFactory $termValidatorFactory,
		array $itemValidators
	) {
		Assert::parameterType( 'integer', $maxTermLength, '$maxLength' );

		$this->maxTermLength = $maxTermLength;
		$this->termValidatorFactory = $termValidatorFactory;
		$this->itemValidators = $itemValidators;
	}

	/**
	 * @return \Wikibase\Lexeme\ChangeOp\Validation\LemmaTermValidator
	 */
	public function getLemmaTermValidator() {
		return new LemmaTermValidator( $this->maxTermLength );
	}

	/**
	 * @return ValueValidator
	 */
	public function getLexicalCategoryValidator() {
		return new CompositeValidator( $this->itemValidators, true );
	}

	/**
	 * @return ValueValidator
	 */
	public function getLanguageValidator() {
		return new CompositeValidator( $this->itemValidators, true );
	}

}
