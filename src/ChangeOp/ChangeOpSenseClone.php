<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataTransfer\BlankSense;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
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
		$this->sourceSense = $sourceSense;
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( BlankSense::class, $entity, '$entity' );

		/** @var BlankSense $entity */

		$entity->setGlosses( $this->sourceSense->getGlosses() );

		// Resets statement GUIDs so they do not mention the former (sense) entity
		// ChangeOpSenseAdd::apply() ensures a new - suitable - GUID is applied once new sense id known
		foreach ( $this->sourceSense->getStatements() as $index => $statement ) {
			$statement->setGuid( null );
			$entity->getStatements()->addStatement( $statement, $index );
		}

		// TODO summary; This is currently only used as part of merging to copy senses
		// from the source lexemes onto the target.
		// Generating a summary here is not necessary as of now.
	}

	public function validate( EntityDocument $entity ): Result {
		Assert::parameterType( BlankSense::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
