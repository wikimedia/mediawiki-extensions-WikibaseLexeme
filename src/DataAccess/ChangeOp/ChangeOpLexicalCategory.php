<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpLexicalCategory extends ChangeOpBase {

	private $lexicalCategory;
	private $lexicalCategoryValidator;

	public function __construct(
		ItemId $lexicalCategory,
		ValueValidator $lexicalCategoryValidator
	) {
		$this->lexicalCategory = $lexicalCategory;
		$this->lexicalCategoryValidator = $lexicalCategoryValidator;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return $this->lexicalCategoryValidator->validate( $this->lexicalCategory );
	}

	/**
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */
		$this->updateSummary( $summary, 'set', '', $this->lexicalCategory->getSerialization() );
		$entity->setLexicalCategory( $this->lexicalCategory );

		return new DummyChangeOpResult();
	}

}
