<?php

namespace Wikibase\Lexeme\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOpBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Providers\LexicalCategoryProvider;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class ChangeOpLexicalCategory extends ChangeOpBase {

	/**
	 * @var ItemId|null
	 */
	private $lexicalCategory;

	/**
	 * @var LexemeValidatorFactory
	 */
	private $lexemeValidatorFactory;

	/**
	 * @param ItemId|null $lexicalCategory
	 * @param LexemeValidatorFactory $lexemeValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		ItemId $lexicalCategory = null,
		LexemeValidatorFactory $lexemeValidatorFactory
	) {
		$this->lexicalCategory = $lexicalCategory;
		$this->lexemeValidatorFactory = $lexemeValidatorFactory;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate( EntityDocument $entity ) {
		Assert::parameterType( LexicalCategoryProvider::class, $entity, '$entity' );

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
		$lexicalCategory = $entity->getLexicalCategory();

		if ( $this->lexicalCategory === null ) {
			if ( $lexicalCategory ) {
				$this->updateSummary(
					$summary,
					'remove',
					'',
					$lexicalCategory->getSerialization()
				);
				$entity->setLexicalCategory( null );
			}

			return;
		}

		$this->updateSummary( $summary, 'set', '', $this->lexicalCategory->getSerialization() );
		$entity->setLexicalCategory( $this->lexicalCategory );
	}

}
