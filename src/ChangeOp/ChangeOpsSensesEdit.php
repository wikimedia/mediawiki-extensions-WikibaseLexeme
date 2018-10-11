<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\SenseId;
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
class ChangeOpsSensesEdit implements ChangeOp {

	private $changeOpForSense = [];

	/**
	 * @param ChangeOp[] $changeOpForSense [ string $senseId => ChangeOp $changeOp ]
	 */
	public function __construct( array $changeOpForSense ) {
		$this->changeOpForSense = $changeOpForSense;
	}

	public function getActions() {
		return [];
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */

		foreach ( $this->changeOpForSense as $senseId => $changeOps ) {
			if ( $entity->getSenses()->getById( new SenseId( $senseId ) ) === null ) {
				return Result::newError( [
					Error::newError(
						'Sense does not exist',
						null,
						'sense-not-found',
						[ $senseId ]
					)
				] );
			}
		}

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		/** @var Lexeme $entity */

		foreach ( $this->changeOpForSense as $senseId => $changeOp ) {
			$sense = $entity->getSenses()->getById( new SenseId( $senseId ) );

			// Passes summary albeit there is no clear definition how summaries should be combined
			$changeOp->apply( $sense, $summary );
		}
	}

}
