<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Api\Summary\FormSummaryAggregator;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpApplyException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * TODO: give me some better name
 * @license GPL-2.0-or-later
 */
class ChangeOpFormEdit implements ChangeOp {

	const SUMMARY_ACTION_AGGREGATE = 'update-form-elements';

	/**
	 * @var ChangeOp[]
	 */
	private $changeOps;

	/**
	 * @var FormSummaryAggregator
	 */
	private $summaryAggregator;

	/**
	 * @param ChangeOp[] $changeOps
	 */
	public function __construct( array $changeOps ) {
		$this->changeOps = $changeOps;
		$this->summaryAggregator = new FormSummaryAggregator( self::SUMMARY_ACTION_AGGREGATE );
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Form::class, $entity, '$entity' );

		/** @var Form $entity */

		foreach ( $this->changeOps as $changeOp ) {
			$subSummary = new Summary();
			$changeOp->apply( $entity, $subSummary );

			if ( $summary !== null ) {
				$this->summaryAggregator->overrideSummary( $summary, $subSummary );
			}
		}

		if ( $entity->getRepresentations()->isEmpty() ) {
			throw new ChangeOpApplyException(
				'wikibaselexeme-api-error-form-must-have-at-least-one-representation'
			);
		}
	}

	public function validate( EntityDocument $entity ) {
		// TODO: should rather combine the validation results from individual change ops
		// OR: return error on first validation error occured
		Assert::parameterType( Form::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function getActions() {
		// TODO: should rather combine the actions of individual change ops
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

	/**
	 * Get the array of change operations.
	 *
	 * @return ChangeOp[]
	 */
	public function getChangeOps() {
		return $this->changeOps;
	}

}
