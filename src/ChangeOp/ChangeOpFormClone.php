<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DummyObjects\BlankForm;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * Copy the properties of the existing Form ($sourceForm) into the passed BlankForm ($entity)
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFormClone implements ChangeOp {

	private $sourceForm;

	/**
	 * @param Form $sourceForm
	 */
	public function __construct( Form $sourceForm ) {
		$this->sourceForm = $sourceForm;
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( BlankForm::class, $entity, '$entity' );

		/** @var BlankForm $entity */

		$entity->setRepresentations( $this->sourceForm->getRepresentations() );
		$entity->setGrammaticalFeatures( $this->sourceForm->getGrammaticalFeatures() );

		// Resets statement GUIDs so they do not mention the former (form) entity
		// ChangeOpFormAdd::apply() ensures a new - suitable - GUID is applied once new form id known
		foreach ( $this->sourceForm->getStatements() as $index => $statement ) {
			$statement->setGuid( null );
			$entity->getStatements()->addStatement( $statement, $index );
		}

		// TODO summary; This is currently only used as part of merging to copy forms
		// from the source lexemes onto the target.
		// Generating a summary here is not necessary as of now.
	}

	public function validate( EntityDocument $entity ): Result {
		Assert::parameterType( BlankForm::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
