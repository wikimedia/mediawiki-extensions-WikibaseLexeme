<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\LexemeValidatorFactory;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpLexicalCategory extends ChangeOpBase {

	/**
	 * @var ItemId
	 */
	private $lexicalCategory;

	/**
	 * @var \Wikibase\Lexeme\LexemeValidatorFactory
	 */
	private $lexemeValidatorFactory;

	/**
	 * @param ItemId $lexicalCategory
	 * @param \Wikibase\Lexeme\LexemeValidatorFactory $lexemeValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		ItemId $lexicalCategory,
		LexemeValidatorFactory $lexemeValidatorFactory
	) {
		$this->lexicalCategory = $lexicalCategory;
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

		$lexicalCategoryValidator = $this->lexemeValidatorFactory->getLexicalCategoryValidator();

		return $lexicalCategoryValidator->validate( $this->lexicalCategory );
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
		$this->updateSummary( $summary, 'set', '', $this->lexicalCategory->getSerialization() );
		$entity->setLexicalCategory( $this->lexicalCategory );
	}

}
