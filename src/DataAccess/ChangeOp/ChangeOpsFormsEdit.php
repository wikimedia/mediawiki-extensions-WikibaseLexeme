<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * This is missing aggregation of summaries but they never would see light of day due to
 * EditEntity::modifyEntity() & EditEntity::getSummary() anyways
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpsFormsEdit implements ChangeOp {

	private $changeOpForForm = [];

	/**
	 * @param ChangeOp[] $changeOpForForm [ string $formId => ChangeOp $changeOp ]
	 */
	public function __construct( array $changeOpForForm ) {
		$this->changeOpForForm = $changeOpForForm;
	}

	public function getActions() {
		return [];
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */

		foreach ( $this->changeOpForForm as $formId => $changeOps ) {
			if ( $entity->getForms()->getById( new FormId( $formId ) ) === null ) {
				return Result::newError( [
					Error::newError(
						'Form does not exist',
						null,
						'form-not-found',
						[ $formId ]
					)
				] );
			}
		}

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		/** @var Lexeme $entity */

		foreach ( $this->changeOpForForm as $formId => $changeOp ) {
			$form = $entity->getForms()->getById( new FormId( $formId ) );

			// Passes summary albeit there is no clear definition how summaries should be combined
			$changeOp->apply( $form, $summary );
		}
	}

}
