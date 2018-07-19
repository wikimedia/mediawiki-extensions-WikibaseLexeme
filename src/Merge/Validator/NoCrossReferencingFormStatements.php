<?php

namespace Wikibase\Lexeme\Merge\Validator;

use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Repo\Merge\Validator\NoCrossReferencingStatements;

/**
 * @license GPL-2.0-or-later
 */
class NoCrossReferencingFormStatements {

	private $upstreamValidator;

	public function __construct( NoCrossReferencingStatements $upstreamValidator ) {
		$this->upstreamValidator = $upstreamValidator;
	}

	/**
	 * @param Lexeme $source
	 * @param Lexeme $target
	 * @return bool
	 */
	public function validate( Lexeme $source, Lexeme $target ) {
		$verdicts = [];

		$sourceForms = $source->getForms()->toArray();
		$targetForms = $target->getForms()->toArray();

		foreach ( $sourceForms as $sourceForm ) {

			foreach ( $targetForms as $targetForm ) {
				$verdicts[] = $this->upstreamValidator->validate( $sourceForm, $targetForm );
			}
			$verdicts[] = $this->upstreamValidator->validate( $sourceForm, $target );
		}

		foreach ( $targetForms as $targetForm ) {
			$verdicts[] = $this->upstreamValidator->validate( $source, $targetForm );
		}

		return !in_array( false, $verdicts, true );
	}

}
