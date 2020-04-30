<?php

namespace Wikibase\Lexeme\Domain\Merge;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormClone;
use Wikibase\Lexeme\Domain\Merge\Validator\FormMergeability;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\Merge\StatementsMerger;

/**
 * @license GPL-2.0-or-later
 */
class LexemeFormsMerger {

	/**
	 * @var StatementsMerger
	 */
	private $statementsMerger;

	/**
	 * @var GuidGenerator
	 */
	private $guidGenerator;

	public function __construct(
		StatementsMerger $statementsMerger,
		GuidGenerator $guidGenerator
	) {
		$this->statementsMerger = $statementsMerger;
		$this->guidGenerator = $guidGenerator;
	}

	/**
	 * This receives Lexeme, not Form as it needs awareness of the Lexeme (id)
	 *
	 * @param Lexeme $source
	 * @param Lexeme $target Will be modified by reference
	 */
	public function merge( Lexeme $source, Lexeme $target ) {
		$formMergeability = new FormMergeability();

		$changeOps = new ChangeOps();

		foreach ( $source->getForms()->toArray() as $sourceForm ) {
			/** @var $sourceForm Form */
			foreach ( $target->getForms()->toArray() as $targetForm ) {
				/** @var $targetForm Form */
				if ( $formMergeability->validate( $sourceForm, $targetForm ) ) {
					$this->mergeForms( $sourceForm, $targetForm );
					continue 2; // source form will only be merged into first matching target form
				}
			}

			$changeOps->add(
				new ChangeOpFormAdd(
					new ChangeOpFormClone( $sourceForm, $this->guidGenerator )
				)
			);
		}

		$changeOps->apply( $target );
	}

	private function mergeForms( Form $source, Form $target ) {
		$target->getRepresentations()->addAll( $source->getRepresentations() );
		$this->statementsMerger->merge( $source, $target );
	}

}
