<?php

namespace Wikibase\Lexeme\Merge;

use Exception;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\Lexeme\Merge\Exceptions\ConflictingLemmaValueException;
use Wikibase\Lexeme\Merge\Exceptions\CrossReferencingException;
use Wikibase\Lexeme\Merge\Exceptions\DifferentLanguagesException;
use Wikibase\Lexeme\Merge\Exceptions\DifferentLexicalCategoriesException;
use Wikibase\Lexeme\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Merge\Exceptions\ModificationFailedException;
use Wikibase\Lexeme\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\Lexeme\Merge\Validator\NoConflictingTermListValues;
use Wikibase\Lexeme\Validators\NoCrossReferencingLexemeStatements;
use Wikibase\Repo\Merge\StatementsMerger;

/**
 * @license GPL-2.0-or-later
 */
class LexemeMerger {

	/**
	 * @var StatementsMerger
	 */
	private $statementsMerger;

	/**
	 * @var LexemeFormsMerger
	 */
	private $formsMerger;

	/**
	 * @var TermListMerger
	 */
	private $termListMerger;

	/**
	 * @var LexemeSensesMerger
	 */
	private $sensesMerger;

	/*
	 * @var NoCrossReferencingLexemeStatements
	 */
	private $noCrossReferencingLexemeStatementsValidator;

	public function __construct(
		TermListMerger $termListMerger,
		StatementsMerger $statementsMerger,
		LexemeFormsMerger $formsMerger,
		LexemeSensesMerger $sensesMerger,
		NoCrossReferencingLexemeStatements $noCrossReferencingLexemeStatementsValidator
	) {
		$this->termListMerger = $termListMerger;
		$this->statementsMerger = $statementsMerger;
		$this->formsMerger = $formsMerger;
		$this->sensesMerger = $sensesMerger;
		$this->noCrossReferencingLexemeStatementsValidator = $noCrossReferencingLexemeStatementsValidator;
	}

	/**
	 * @param Lexeme $source
	 * @param Lexeme $target Will be modified by reference
	 */
	public function merge( Lexeme $source, Lexeme $target ) {
		$this->validate( $source, $target );

		try {
			$this->termListMerger->merge( $source->getLemmas(), $target->getLemmas() );
			$this->formsMerger->merge( $source, $target );
			$this->sensesMerger->merge( $source, $target );
			$this->statementsMerger->merge( $source, $target );
		} catch ( MergingException $e ) {
			throw $e;
		} catch ( Exception $e ) {
			throw new ModificationFailedException( '', 0, $e );
		}
	}

	private function validate( Lexeme $source, Lexeme $target ) {
		if ( $source->getId()->equals( $target->getId() ) ) {
			throw new ReferenceSameLexemeException();
		}

		if ( !$source->getLanguage()->equals( $target->getLanguage() ) ) {
			throw new DifferentLanguagesException();
		}

		if ( !$source->getLexicalCategory()->equals( $target->getLexicalCategory() ) ) {
			throw new DifferentLexicalCategoriesException();
		}

		$conflictingTermListValues = new NoConflictingTermListValues();
		if ( !$conflictingTermListValues->validate( $source->getLemmas(), $target->getLemmas() ) ) {
			throw new ConflictingLemmaValueException();
		}

		if ( !$this->noCrossReferencingLexemeStatementsValidator->validate( $source, $target ) ) {
			throw new CrossReferencingException();
		}
	}

}
