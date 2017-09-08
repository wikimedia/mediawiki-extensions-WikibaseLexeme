<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * TODO: Is this class actually needed? Could probably be removed
 */
class ChangeOpAddForm extends ChangeOpBase {

	/**
	 * @var TermList
	 */
	private $representations;
	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	/**
	 * @param TermList $representations
	 * @param ItemId[] $grammaticalFeatures
	 */
	public function __construct( TermList $representations, array $grammaticalFeatures ) {
		$this->representations = $representations;
		$this->grammaticalFeatures = $grammaticalFeatures;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */
		$form = $entity->addForm( $this->representations, $this->grammaticalFeatures );

		$this->updateSummary(
			$summary,
			'add-form',
			'',
			array_values( $form->getRepresentations()->toTextArray() )
		);
	}

}
