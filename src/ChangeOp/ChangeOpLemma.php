<?php

namespace Wikibase\Lexeme\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class ChangeOpLemma extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var string|null
	 */
	private $lemma;

	/**
	 * @var LexemeValidatorFactory
	 */
	private $lexemeValidatorFactory;

	/**
	 * @param string $language
	 * @param string|null $lemma
	 * @param LexemeValidatorFactory $lexemeValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, $lemma, LexemeValidatorFactory $lexemeValidatorFactory ) {
		Assert::parameterType( 'string', $language, '$language' );
		Assert::parameterType( 'string|null', $lemma, '$lemma' );

		$this->language = $language;
		$this->lemma = $lemma;
		$this->lexemeValidatorFactory = $lexemeValidatorFactory;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		$languageValidator = $this->lexemeValidatorFactory->getLanguageCodeValidator();
		$termValidator = $this->lexemeValidatorFactory->getLemmaTermValidator();

		$result = $languageValidator->validate( $this->language );
		if ( $result->isValid() && $this->lemma !== null ) {
			$result = $termValidator->validate( $this->lemma );
		}

		// TODO: this should probably also check that the (language, lemma) pair is unique

		return $result;
	}

	/**
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		// NOTE: This part is very likely to change completely once a decision
		//       about the lemma representation has been made.

		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */
		$lemmas = $entity->getLemmas();
		$hasLemma = $lemmas->hasTermForLanguage( $this->language );

		if ( $this->lemma === null ) {
			if ( $hasLemma ) {
				$oldLemma = $lemmas->getByLanguage( $this->language )->getText();
				$this->updateSummary( $summary, 'remove', $this->language, $oldLemma );
				$lemmas->removeByLanguage( $this->language );
			}

			return;
		}

		$action = $hasLemma ? 'set' : 'add';
		$this->updateSummary( $summary, $action, $this->language, $this->lemma );
		$lemmas->setTextForLanguage( $this->language, $this->lemma );
	}

}
