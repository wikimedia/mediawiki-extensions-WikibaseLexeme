<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\Patcher\ListPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityPatcherStrategy;
use Wikibase\DataModel\Services\Diff\StatementListPatcher;
use Wikibase\DataModel\Services\Diff\TermListPatcher;
use Wikibase\Lexeme\Domain\Model\Form;

/**
 * @license GPL-2.0-or-later
 */
class FormPatcher implements EntityPatcherStrategy {

	private $termListPatcher;

	private $statementListPatcher;

	private $listPatcher;

	public function __construct() {
		$this->termListPatcher = new TermListPatcher();
		$this->statementListPatcher = new StatementListPatcher();
		$this->listPatcher = new ListPatcher();
	}

	/**
	 * @param string $entityType
	 *
	 * @return bool
	 */
	public function canPatchEntityType( $entityType ) {
		return $entityType === Form::ENTITY_TYPE;
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		if ( !( $entity instanceof Form ) ) {
			throw new InvalidArgumentException( 'Can only patch Forms' );
		}

		// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType
		return $this->patch( $entity, $patch );
	}

	/**
	 * @deprecated use self::patchEntity instead
	 *
	 * @param Form $form
	 * @param ChangeFormDiffOp $diff
	 */
	public function patch( Form $form, ChangeFormDiffOp $diff ) {
		$this->termListPatcher->patchTermList(
			$form->getRepresentations(),
			$diff->getRepresentationDiff()
		);
		$grammaticalFeatures = $form->getGrammaticalFeatures();
		$patchedGrammaticalFeatures = $this->listPatcher->patch(
			$grammaticalFeatures,
			$diff->getGrammaticalFeaturesDiff()
		);
		$form->setGrammaticalFeatures( $patchedGrammaticalFeatures );

		$this->statementListPatcher->patchStatementList(
			$form->getStatements(),
			$diff->getStatementsDiff()
		);
	}

}
