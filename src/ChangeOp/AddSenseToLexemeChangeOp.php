<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\Lexeme\DummyObjects\BlankSense;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class AddSenseToLexemeChangeOp implements ChangeOp {

	/**
	 * @var Lexeme
	 */
	private $lexeme;

	public function __construct( Lexeme $lexeme ) {
		$this->lexeme = $lexeme;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( BlankSense::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( BlankSense::class, $entity, '$entity' );

		/** @var BlankSense $entity */
		$entity->setLexeme( $this->lexeme );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
