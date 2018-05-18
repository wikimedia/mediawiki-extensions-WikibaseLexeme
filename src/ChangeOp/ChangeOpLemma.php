<?php

namespace Wikibase\Lexeme\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
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
	 * @var ValueValidator
	 */
	private $lemmaTermValidator;

	/**
	 * @param string $language
	 * @param string|null $lemma
	 * @param ValueValidator $lemmaTermValidator
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, $lemma, ValueValidator $lemmaTermValidator ) {
		Assert::parameterType( 'string', $language, '$language' );
		Assert::parameterType( 'string|null', $lemma, '$lemma' );

		$this->language = $language;
		$this->lemma = $lemma;
		$this->lemmaTermValidator = $lemmaTermValidator;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		// TODO Create dedicated ChangeOpRemoveLemma, use from LemmaChangeOpDeserializer
		if ( $this->lemma !== null ) { // magic removal instruction from deserializer
			return $this->lemmaTermValidator->validate( $this->lemma );
		}

		return Result::newSuccess();
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
