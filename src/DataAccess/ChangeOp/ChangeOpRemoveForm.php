<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveForm extends ChangeOpBase {

	private const SUMMARY_ACTION_REMOVE = 'remove-form';

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @param FormId $formId The FormId to remove
	 */
	public function __construct( FormId $formId ) {
		$this->formId = $formId;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */
		if ( $entity->getForms()->getById( $this->formId ) === null ) {
			return Result::newError( [
				Error::newError(
					'Form does not exist',
					null,
					'form-not-found',
					[ $this->formId->serialize() ]
				),
			] );
		}

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */

		$form = $entity->getForm( $this->formId );
		$entity->removeForm( $this->formId );

		$this->updateSummary(
			$summary,
			self::SUMMARY_ACTION_REMOVE,
			'',
			array_values( $form->getRepresentations()->toTextArray() )
		);

		return new DummyChangeOpResult();
	}

	protected function updateSummary( ?Summary $summary, $action, $language = '', $args = '' ) {
		parent::updateSummary( $summary, $action, $language, $args );
		if ( $summary !== null ) {
			$summary->addAutoCommentArgs( [ $this->formId->getSerialization() ] );
		}
	}

}
