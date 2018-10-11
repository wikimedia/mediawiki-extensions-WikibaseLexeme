<?php

namespace Wikibase\Lexeme\Domain\Merge\Validator;

use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Domain\Model\Form;

/**
 * @license GPL-2.0-or-later
 */
class FormMergeability {

	/**
	 * @param Form $source
	 * @param Form $target
	 * @return bool
	 */
	public function validate( Form $source, Form $target ) {
		return (
			$this->haveIdenticalGrammaticalFeatures( $source, $target )
			&&
			$this->haveACommonRepresentation( $source, $target )
			&&
			!$this->haveContradictingRepresentations( $source, $target )
		);
	}

	/**
	 * @param Form $source
	 * @param Form $target
	 * @return bool
	 */
	private function haveIdenticalGrammaticalFeatures( Form $source, Form $target ) {
		return ( $source->getGrammaticalFeatures() == $target->getGrammaticalFeatures() );
	}

	/**
	 * @param Form $source
	 * @param Form $target
	 * @return bool
	 */
	private function haveACommonRepresentation( Form $source, Form $target ) {
		$sourceRepresentations = $source->getRepresentations();
		$targetRepresentations = $target->getRepresentations();

		foreach ( $sourceRepresentations as $representation ) {
			/** @var $representation Term */
			if ( $targetRepresentations->hasTerm( $representation ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Form $source
	 * @param Form $target
	 * @return bool
	 */
	private function haveContradictingRepresentations( Form $source, Form $target ) {
		$conflictingTermListValues = new NoConflictingTermListValues();
		return !$conflictingTermListValues->validate(
			$source->getRepresentations(), $target->getRepresentations()
		);
	}

}
