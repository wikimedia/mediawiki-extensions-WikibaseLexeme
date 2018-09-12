<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DummyObjects\BlankForm;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class AddFormToLexemeChangeOp implements ChangeOp {

	/**
	 * @var Lexeme
	 */
	private $lexeme;

	public function __construct( Lexeme $lexeme ) {
		$this->lexeme = $lexeme;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( BlankForm::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( BlankForm::class, $entity, '$entity' );

		/** @var BlankForm $entity */
		$entity->setLexeme( $this->lexeme );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
