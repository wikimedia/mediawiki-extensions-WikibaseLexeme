<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DummyObjects\BlankForm;
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

	private $guidGenerator;

	/**
	 * @param ChangeOp $changeOpForm
	 * @param GuidGenerator $guidGenerator
	 */
	public function __construct( ChangeOp $changeOpForm, GuidGenerator $guidGenerator ) {
		$this->changeOpForm = $changeOpForm;
		$this->guidGenerator = $guidGenerator;
	}

	public function validate( EntityDocument $entity ) : Result {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */

		$form = new BlankForm();

		$entity->addOrUpdateForm( $form );
		$this->changeOpForm->apply( $form, null );

		// update statements to have a suitable guid now that the new form id is known
		// fixme This should find a new home in a more central place, maybe StatementList
		foreach ( $form->getStatements() as $statement ) {
			$statement->setGuid( $this->guidGenerator->newGuid( $form->getId() ) );
		}

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
