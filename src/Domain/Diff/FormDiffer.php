<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use DomainException;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDifferStrategy;
use Wikibase\DataModel\Services\Diff\StatementListDiffer;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\Domain\Model\Form;

/**
 * @license GPL-2.0-or-later
 * @phan-file-suppress PhanPluginNeverReturnMethod
 */
class FormDiffer implements EntityDifferStrategy {

	/**
	 * @var MapDiffer
	 */
	private $recursiveMapDiffer;

	/**
	 * @var StatementListDiffer
	 */
	private $statementListDiffer;

	public function __construct() {
		$this->recursiveMapDiffer = new MapDiffer( true );
		$this->statementListDiffer = new StatementListDiffer();
	}

	/**
	 * @param string $entityType
	 *
	 * @return bool
	 */
	public function canDiffEntityType( $entityType ) {
		return $entityType === Form::ENTITY_TYPE;
	}

	/**
	 * @param EntityDocument $from
	 * @param EntityDocument $to
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		if ( !( $from instanceof Form ) || !( $to instanceof Form ) ) {
			throw new InvalidArgumentException( 'Can only diff Forms' );
		}

		return $this->diff( $from, $to );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getConstructionDiff( EntityDocument $entity ) {
		throw new DomainException( 'Forms aren\'t stored as separate wiki pages, and can only show '
			. 'up in regular diffs that add or remove a Form' );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		throw new DomainException( 'Forms aren\'t stored as separate wiki pages, and can only show '
			. 'up in regular diffs that add or remove a Form' );
	}

	/**
	 * @param Form $old
	 * @param Form $new
	 *
	 * @return ChangeFormDiffOp
	 */
	public function diff( Form $old, Form $new ) {
		// TODO: Assert same ID
		$diffOps = $this->recursiveMapDiffer->doDiff(
			$this->toFormDiffArray( $old ),
			$this->toFormDiffArray( $new )
		);

		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			$old->getStatements(),
			$new->getStatements()
		);

		return new ChangeFormDiffOp( $old->getId(), new Diff( $diffOps ) );
	}

	public function getAddFormDiff( Form $form ) {
		$diffOps = $this->recursiveMapDiffer->doDiff(
			[],
			$this->toFormDiffArray( $form )
		);
		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			new StatementList(),
			$form->getStatements()
		);

		return new AddFormDiff( $form, new Diff( $diffOps ) );
	}

	public function getRemoveFormDiff( Form $form ) {
		$diffOps = $this->recursiveMapDiffer->doDiff(
			$this->toFormDiffArray( $form ),
			[]
		);
		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			$form->getStatements(),
			new StatementList()
		);

		return new RemoveFormDiff( $form->getId(), new Diff( $diffOps ) );
	}

	/**
	 * @param Form $form
	 *
	 * @return array
	 */
	private function toFormDiffArray( Form $form ) {
		$result = [];
		$result['representations'] = $form->getRepresentations()->toTextArray();
		$result['grammaticalFeatures'] = $form->getGrammaticalFeatures();

		return $result;
	}

}
