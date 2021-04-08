<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\Assert;

/**
 * Copy the properties of the existing Sense ($sourceSense) into the passed BlankSense ($entity)
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseClone implements ChangeOp {

	private $sourceSense;

	/**
	 * @param Sense $sourceSense
	 */
	public function __construct( Sense $sourceSense ) {
		$this->sourceSense = $sourceSense->copy();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( BlankSense::class, $entity, '$entity' );
		'@phan-var Sense $entity';

		/** @var Sense $entity */

		$entity->getGlosses()->addAll( $this->sourceSense->getGlosses() );

		// Resets statement GUIDs so they do not mention the former (sense) entity
		// ChangeOpSenseAdd::apply() ensures a new - suitable - GUID is applied once new sense id known
		foreach ( $this->sourceSense->getStatements() as $index => $statement ) {
			$statement->setGuid( null );
			$entity->getStatements()->addStatement( $statement, $index );
		}

		// TODO summary; This is currently only used as part of merging to copy senses
		// from the source lexemes onto the target.
		// Generating a summary here is not necessary as of now.

		return new DummyChangeOpResult();
	}

	public function validate( EntityDocument $entity ): Result {
		Assert::parameterType( BlankSense::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
