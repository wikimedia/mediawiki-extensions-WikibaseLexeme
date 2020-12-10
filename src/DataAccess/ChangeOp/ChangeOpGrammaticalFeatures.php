<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpGrammaticalFeatures implements ChangeOp {

	private const SUMMARY_ACTION_ADD = 'add-form-grammatical-features';
	private const SUMMARY_ACTION_REMOVE = 'remove-form-grammatical-features';
	private const SUMMARY_ACTION_UPDATE = 'update-form-grammatical-features';

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	public function __construct( array $grammaticalFeatures ) {
		$this->grammaticalFeatures = $grammaticalFeatures;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Form::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Form::class, $entity, '$entity' );
		'@phan-var Form $entity';

		$this->updateSummary( $entity, $summary );

		/** @var Form $entity */
		$entity->setGrammaticalFeatures( $this->grammaticalFeatures );

		return new DummyChangeOpResult();
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

	private function updateSummary( Form $form, Summary $summary = null ) {
		if ( $summary === null ) {
			return;
		}

		$existingFeatures = $form->getGrammaticalFeatures();

		$addedFeatures = array_diff( $this->grammaticalFeatures, $existingFeatures );
		$removedFeatures = array_diff( $existingFeatures, $this->grammaticalFeatures );

		$formId = $form->getId();

		if ( !empty( $addedFeatures ) && !empty( $removedFeatures ) ) {
			$summary->setAction( self::SUMMARY_ACTION_UPDATE );
			$summary->setLanguage( null );
			$summary->addAutoCommentArgs( [
				$formId->getSerialization() // TODO: use FormId not string?
			] );
			return;
		}

		if ( !empty( $addedFeatures ) ) {
			$summary->setAction( self::SUMMARY_ACTION_ADD );
			$summary->setLanguage( null );
			$summary->addAutoCommentArgs( [
				$formId->getSerialization() // TODO: use FormId not string?
			] );
			$summary->addAutoSummaryArgs( $addedFeatures );
		}

		if ( !empty( $removedFeatures ) ) {
			$summary->setAction( self::SUMMARY_ACTION_REMOVE );
			$summary->setLanguage( null );
			$summary->addAutoCommentArgs( [
				$formId->getSerialization() // TODO: use FormId not string?
			] );
			$summary->addAutoSummaryArgs( $removedFeatures );
		}
	}

}
