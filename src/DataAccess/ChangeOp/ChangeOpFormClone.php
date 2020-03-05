<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\Assert;

/**
 * Copy the properties of the existing Form ($sourceForm) into the passed BlankForm ($entity)
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFormClone implements ChangeOp {

	/**
	 * @var Form
	 */
	private $sourceForm;

	/**
	 * @var GuidGenerator
	 */
	private $guidGenerator;

	/**
	 * @param Form $sourceForm
	 */
	public function __construct( Form $sourceForm, GuidGenerator $guidGenerator ) {
		$this->sourceForm = $sourceForm->copy();
		$this->guidGenerator = $guidGenerator;
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Form::class, $entity, '$entity' );
		'@phan-var Form $entity';
		/** @var Form $entity */

		$entity->setRepresentations( $this->sourceForm->getRepresentations() );
		$entity->setGrammaticalFeatures( $this->sourceForm->getGrammaticalFeatures() );

		foreach ( $this->sourceForm->getStatements() as $index => $statement ) {
			// update statements to have a suitable GUID based on the form id
			// fixme Maybe this can find a new home in a more central place, e.g. StatementList
			$statement->setGuid( $this->guidGenerator->newGuid( $entity->getId() ) );

			$entity->getStatements()->addStatement( $statement, $index );
		}

		// TODO summary; This is currently only used as part of merging to copy forms
		// from the source lexemes onto the target.
		// Generating a summary here is not necessary as of now.

		return new DummyChangeOpResult();
	}

	public function validate( EntityDocument $entity ): Result {
		Assert::parameterType( Form::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
