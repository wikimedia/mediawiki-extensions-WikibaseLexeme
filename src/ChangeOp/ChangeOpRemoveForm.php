<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveForm extends ChangeOpBase {

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

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */
		if ( $entity->getForms()->getById( $this->formId ) === null ) {
			throw new ChangeOpException( "Lexeme does not have Form with ID $this->formId" );
		}

		$form = $entity->getForm( $this->formId );
		$entity->removeForm( $this->formId );

		$this->updateSummary(
			$summary,
			'remove-form',
			'',
			array_values( $form->getRepresentations()->toTextArray() )
		);
	}

	protected function updateSummary( Summary $summary = null, $action, $language = '', $args = '' ) {
		parent::updateSummary( $summary, $action, $language, $args );
		if ( $summary !== null ) {
			$summary->addAutoCommentArgs( [ $this->formId->getSerialization() ] );
		}
	}

}
