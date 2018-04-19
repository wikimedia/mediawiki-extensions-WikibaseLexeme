<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataTransfer\BlankForm;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpFormAdd extends ChangeOpBase {

	const SUMMARY_ACTION_ADD = 'add-form';

	/**
	 * @var ChangeOp
	 */
	private $changeOpForm;

	/**
	 * @param ChangeOp $changeOpForm
	 */
	public function __construct( ChangeOp $changeOpForm ) {
		$this->changeOpForm = $changeOpForm;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */

		$blankForm = new BlankForm();
		$blankForm->setLexeme( $entity );

		$this->changeOpForm->apply( $blankForm, null );

		$form = $entity->addForm(
			$blankForm->getRepresentations(),
			$blankForm->getGrammaticalFeatures()
		);

		if ( $summary !== null ) {
			// TODO: consistently do not extend ChangeOpBase?
			$this->updateSummary(
				$summary,
				self::SUMMARY_ACTION_ADD,
				null,
				array_values( $form->getRepresentations()->toTextArray() )
			);
			// TODO: use FormId not string?
			$summary->addAutoCommentArgs( $form->getId()->getSerialization() );
		}
	}

}
