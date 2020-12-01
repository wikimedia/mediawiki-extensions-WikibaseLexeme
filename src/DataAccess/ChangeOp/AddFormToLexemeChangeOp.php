<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\EntityPermissionChecker;
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
		'@phan-var BlankForm $form';

		/** @var BlankForm $form */
		$this->lexeme->addOrUpdateForm( $form );
		$this->changeOpFormEdit->apply( $form );

		return new DummyChangeOpResult();
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
