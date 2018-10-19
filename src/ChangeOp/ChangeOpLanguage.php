<?php

namespace Wikibase\Lexeme\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpLanguage extends ChangeOpBase {

	/**
	 * @var ItemId
	 */
	private $language;

	/**
	 * @var LexemeValidatorFactory
	 */
	private $lexemeValidatorFactory;

	/**
	 * @param ItemId $language
	 * @param LexemeValidatorFactory $lexemeValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		ItemId $language,
		LexemeValidatorFactory $lexemeValidatorFactory
	) {
		$this->language = $language;
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

		$languageValidator = $this->lexemeValidatorFactory->getLanguageValidator();

		return $languageValidator->validate( $this->language );
	}

	/**
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */
		$this->updateSummary( $summary, 'set', '', $this->language->getSerialization() );
		$entity->setLanguage( $this->language );
	}

}
