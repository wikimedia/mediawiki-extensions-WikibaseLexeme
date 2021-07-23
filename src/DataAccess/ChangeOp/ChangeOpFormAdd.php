<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpFormAdd extends ChangeOpBase {

	private const SUMMARY_ACTION_ADD = 'add-form';

	/**
	 * @var ChangeOp
	 */
	private $changeOpForm;

	/**
	 * @param ChangeOp $changeOpForm
	 */
	public function __construct( ChangeOp $changeOpForm ) {
		$this->changeOpForm = $changeOpForm;
	}

	public function validate( EntityDocument $entity ): Result {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */

		$form = new BlankForm();

		$entity->addOrUpdateForm( $form );
		$this->changeOpForm->apply( $form, null );

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

		return new DummyChangeOpResult();
	}

}
