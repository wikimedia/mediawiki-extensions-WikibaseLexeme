<?php

namespace Wikibase\Lexeme;

use ValueValidators\ValueValidator;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikimedia\Assert\Assert;

/**
 * Provides validators that can be used to validates elements of the Lexeme entity.
 *
 * @license GPL-2.0-or-later
 */
class LexemeValidatorFactory {

	private $maxTermLength;
	private $termValidatorFactory;
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

	public function getLemmaTermValidator(): LemmaTermValidator {
		return new LemmaTermValidator( $this->maxTermLength );
	}

	public function getLexicalCategoryValidator(): ValueValidator {
		return new CompositeValidator( $this->itemValidators, true );
	}

	public function getLanguageValidator(): ValueValidator {
		return new CompositeValidator( $this->itemValidators, true );
	}

}
