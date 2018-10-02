<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\Form;
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

	/**
	 * @var ChangeOp
	 */
	private $changeOpFormEdit;

	public function __construct( Lexeme $lexeme, ChangeOp $changeOpFormEdit ) {
		$this->lexeme = $lexeme;
		$this->changeOpFormEdit = $changeOpFormEdit;
	}

	public function validate( EntityDocument $form ) {
		Assert::parameterType( BlankForm::class, $form, '$form' );

		$this->changeOpFormEdit->validate( $form );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $form, Summary $summary = null ) {
		Assert::parameterType( BlankForm::class, $form, '$form' );

		/** @var BlankForm $form */
		$this->lexeme->addOrUpdateForm( $form );
		$this->changeOpFormEdit->apply( $form );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
