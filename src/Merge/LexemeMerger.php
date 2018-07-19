<?php

namespace Wikibase\Lexeme\Merge;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Merge\Exceptions\ConflictingLemmaValueException;
use Wikibase\Lexeme\Merge\Exceptions\CrossReferencingException;
use Wikibase\Lexeme\Merge\Exceptions\DifferentLanguagesException;
use Wikibase\Lexeme\Merge\Exceptions\DifferentLexicalCategoriesException;
use Wikibase\Lexeme\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\Lexeme\Merge\Validator\NoConflictingTermListValues;
use Wikibase\Repo\Merge\StatementsMerger;
use Wikibase\Repo\Merge\Validator\NoCrossReferencingStatements;

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

	public function __construct( StatementsMerger $statementsMerger ) {
		$this->statementsMerger = $statementsMerger;
		$this->termListMerger = new TermListMerger();
		$this->formsMerger = new LexemeFormsMerger(
			$this->statementsMerger,
			$this->termListMerger,
			new GuidGenerator()
		);
	}

	/**
	 * @param Lexeme $source
	 * @param Lexeme $target Will be modified by reference
	 */
	public function merge( Lexeme $source, Lexeme $target ) {
		$this->validate( $source, $target );

		$this->termListMerger->merge( $source->getLemmas(), $target->getLemmas() );
		$this->formsMerger->merge( $source, $target );
		$this->statementsMerger->merge( $source, $target );
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

		$crossReferencingStatements = new NoCrossReferencingStatements();
		if ( !$crossReferencingStatements->validate( $source, $target ) ) {
			throw new CrossReferencingException();
		}
	}

}
