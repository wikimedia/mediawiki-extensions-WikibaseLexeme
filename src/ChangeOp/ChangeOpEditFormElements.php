<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class ChangeOpEditFormElements implements ChangeOp {

	/**
	 * @var TermList
	 */
	private $representations;

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	public function __construct( TermList $representations, array $grammaticalFeatures ) {
		$this->representations = $representations;
		$this->grammaticalFeatures = $grammaticalFeatures;
	}

	public function validate( EntityDocument $entity ) {
		// TODO: Should this be also a change op applicable on Lexeme entities
		// (e.g. when used in wbeditentity)?
		Assert::parameterType( Form::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		// TODO: Should this be also a change op applicable on Lexeme entities
		// (e.g. when used in wbeditentity)?
		Assert::parameterType( Form::class, $entity, '$entity' );

		/** @var Form $entity */
		$entity->setRepresentations( $this->representations );
		$entity->setGrammaticalFeatures( $this->grammaticalFeatures );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
