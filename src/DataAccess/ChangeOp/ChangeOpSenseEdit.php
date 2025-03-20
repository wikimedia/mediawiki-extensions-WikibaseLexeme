<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\MediaWiki\Api\Summary\SummaryAggregator;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpApplyException;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\Assert;

/**
 * TODO: give me some better name
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseEdit implements ChangeOp {

	private const SUMMARY_ACTION_AGGREGATE = 'update-sense-elements';

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

	/** @inheritDoc */
	public function apply( EntityDocument $entity, ?Summary $summary = null ) {
		Assert::parameterType( Sense::class, $entity, '$entity' );
		'@phan-var Sense $entity';

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
				'apierror-wikibaselexeme-sense-must-have-at-least-one-gloss'
			);
		}

		return new DummyChangeOpResult();
	}

	/** @inheritDoc */
	public function validate( EntityDocument $entity ) {
		// TODO: should rather combine the validation results from individual change ops
		// OR: return error on first validation error occurred
		Assert::parameterType( Sense::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	/** @inheritDoc */
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
