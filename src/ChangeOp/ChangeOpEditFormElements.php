<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Api\Summary\AddGrammaticalFeatureSummary;
use Wikibase\Lexeme\Api\Summary\AddRepresentationSummary;
use Wikibase\Lexeme\Api\Summary\ChangeFormElementsSummary;
use Wikibase\Lexeme\Api\Summary\RemoveGrammaticalFeatureSummary;
use Wikibase\Lexeme\Api\Summary\RemoveRepresentationSummary;
use Wikibase\Lexeme\Api\Summary\SetRepresentationSummary;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpEditFormElements implements ChangeOp {

	/**
	 * @var TermList
	 */
	private $representations;

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	public function __construct( TermList $representations, array $grammaticalFeatures ) {
		$this->representations = $representations;
		$this->grammaticalFeatures = $grammaticalFeatures;
	}

	public function validate( EntityDocument $entity ) {
		// TODO: Should this be also a change op applicable on Lexeme entities
		// (e.g. when used in wbeditentity)?
		Assert::parameterType( Form::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		// TODO: Should this be also a change op applicable on Lexeme entities
		// (e.g. when used in wbeditentity)?
		Assert::parameterType( Form::class, $entity, '$entity' );

		/** @var Form $entity */
		$entity->setRepresentations( $this->representations );
		$entity->setGrammaticalFeatures( $this->grammaticalFeatures );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

	public function generateSummaryWhenAppliedToForm( Form $form ) {
		$representationSummary = $this->getRepresentationSummary( $form );
		$grammaticalFeaturesSummary = $this->getGrammaticalFeatureSummaries( $form );

		if ( $representationSummary instanceof ChangeFormElementsSummary ) {
			return $representationSummary;
		}

		if ( $grammaticalFeaturesSummary instanceof ChangeFormElementsSummary ) {
			return $grammaticalFeaturesSummary;
		}

		if ( $representationSummary !== null && $grammaticalFeaturesSummary !== null ) {
			return new ChangeFormElementsSummary( $form->getId() );
		}

		if ( $representationSummary !== null ) {
			return $representationSummary;
		}
		if ( $grammaticalFeaturesSummary !== null ) {
			return $grammaticalFeaturesSummary;
		}

		return null;
	}

	/**
	 * @param Form $form
	 * @return FormatableSummary|null
	 */
	private function getRepresentationSummary( Form $form ) {
		$existingRepresentations = $form->getRepresentations()->toTextArray();

		$addedRepresentations = array_diff_key(
			$this->representations->toTextArray(),
			$existingRepresentations
		);
		$removedRepresentations = array_diff_key(
			$existingRepresentations,
			$this->representations->toTextArray()
		);

		$changedRepresentations = array_diff_key(
			array_diff_assoc( $this->representations->toTextArray(), $existingRepresentations ),
			$addedRepresentations
		);

		$hasAddedRepresentations = !empty( $addedRepresentations );
		$hasRemovedRepresentations = !empty( $removedRepresentations );
		$hasChangedRepresentations = !empty( $changedRepresentations );

		$formId = $form->getId();

		$summary = null;
		if ( $hasAddedRepresentations ) {
			$summary = new AddRepresentationSummary( $formId, $addedRepresentations );
		}

		if ( $summary !== null && ( $hasRemovedRepresentations || $hasChangedRepresentations ) ) {
			return new ChangeFormElementsSummary( $formId );
		}

		if ( $hasRemovedRepresentations ) {
			$summary = new RemoveRepresentationSummary( $formId, $removedRepresentations );
		}

		if ( $summary !== null && $hasChangedRepresentations ) {
			return new ChangeFormElementsSummary( $formId );
		}

		if ( $hasChangedRepresentations ) {
			$summary = new SetRepresentationSummary( $formId, $changedRepresentations );
		}

		return $summary;
	}

	/**
	 * @param Form $form
	 * @return FormatableSummary|null
	 */
	private function getGrammaticalFeatureSummaries( Form $form ) {
		$existingFeatures = $form->getGrammaticalFeatures();

		$addedFeatures = array_diff( $this->grammaticalFeatures, $existingFeatures );
		$removedFeatures = array_diff( $existingFeatures, $this->grammaticalFeatures );

		$formId = $form->getId();

		$summary = null;
		if ( !empty( $addedFeatures ) ) {
			$summary = new AddGrammaticalFeatureSummary( $formId, array_values( $addedFeatures ) );
		}

		if ( $summary !== null && !empty( $removedFeatures ) ) {
			return new ChangeFormElementsSummary( $formId );
		}

		if ( !empty( $removedFeatures ) ) {
			$summary = new RemoveGrammaticalFeatureSummary( $formId, array_values( $removedFeatures ) );
		}

		return $summary;
	}

}
