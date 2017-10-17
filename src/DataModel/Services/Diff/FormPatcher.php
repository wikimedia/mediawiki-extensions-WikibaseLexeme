<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\Patcher\ListPatcher;
use Wikibase\DataModel\Services\Diff\StatementListPatcher;
use Wikibase\DataModel\Services\Diff\TermListPatcher;
use Wikibase\Lexeme\DataModel\Form;

/**
 * @license GPL-2.0+
 */
class FormPatcher {

	public function __construct() {
		$this->termListPatcher = new TermListPatcher();
		$this->statementListPatcher = new StatementListPatcher();
		$this->listPatcher = new ListPatcher();
	}

	public function patch( Form $form, ChangeFormDiffOp $diff ) {
		$this->termListPatcher->patchTermList(
			$form->getRepresentations(),
			$diff->getRepresentationDiffOps()
		);
		$grammaticalFeatures = $form->getGrammaticalFeatures();
		$patchedGrammaticalFeatures = $this->listPatcher->patch(
			$grammaticalFeatures,
			$diff->getGrammaticalFeaturesDiffOps()
		);
		$form->setGrammaticalFeatures( $patchedGrammaticalFeatures );

		$this->statementListPatcher->patchStatementList(
			$form->getStatements(),
			$diff->getStatementsDiffOps()
		);
	}

}
