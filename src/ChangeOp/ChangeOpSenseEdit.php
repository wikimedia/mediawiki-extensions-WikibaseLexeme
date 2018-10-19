<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\MediaWiki\Api\Summary\SummaryAggregator;
use Wikibase\Lexeme\Domain\DataModel\Sense;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpApplyException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * TODO: give me some better name
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseEdit implements ChangeOp {

	const SUMMARY_ACTION_AGGREGATE = 'update-sense-elements';

	/**
	 * @var ChangeOp[]
	 */
	private $changeOps;

	/**
	 * @var SummaryAggregator
	 */
	private $summaryAggregator;

	/**
	 * @param ChangeOp[] $changeOps
	 */
	public function __construct( array $changeOps ) {
		$this->changeOps = $changeOps;
		$this->summaryAggregator = new SummaryAggregator( self::SUMMARY_ACTION_AGGREGATE );
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Sense::class, $entity, '$entity' );

		/** @var Sense $entity */

		foreach ( $this->changeOps as $changeOp ) {
			$subSummary = new Summary();
			$changeOp->apply( $entity, $subSummary );

			if ( $summary !== null ) {
				$this->summaryAggregator->overrideSummary( $summary, $subSummary );
			}
		}

		if ( $entity->getGlosses()->isEmpty() ) {
			throw new ChangeOpApplyException(
				'wikibaselexeme-api-error-sense-must-have-at-least-one-gloss'
			);
		}
	}

	public function validate( EntityDocument $entity ) {
		// TODO: should rather combine the validation results from individual change ops
		// OR: return error on first validation error occurred
		Assert::parameterType( Sense::class, $entity, '$entity' );

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
