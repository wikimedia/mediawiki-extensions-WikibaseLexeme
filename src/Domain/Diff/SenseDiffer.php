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
use Wikibase\Lexeme\Domain\Model\Sense;

/**
 * @license GPL-2.0-or-later
 * @phan-file-suppress PhanPluginNeverReturnMethod
 */
class SenseDiffer implements EntityDifferStrategy {

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
		return $entityType === 'sense';
	}

	/**
	 * @param EntityDocument $from
	 * @param EntityDocument $to
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		if ( !( $from instanceof Sense ) || !( $to instanceof Sense ) ) {
			throw new InvalidArgumentException( 'Can only diff Senses' );
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
		throw new DomainException( 'Sense aren\'t stored as separate wiki pages, and can only show '
			. 'up in regular diffs that add or remove a Sense' );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		throw new DomainException( 'Senses aren\'t stored as separate wiki pages, and can only show '
			. 'up in regular diffs that add or remove a Sense' );
	}

	/**
	 * @param Sense $old
	 * @param Sense $new
	 *
	 * @return ChangeSenseDiffOp
	 */
	private function diff( Sense $old, Sense $new ) {
		// TODO: Assert same ID
		$diffOps = $this->recursiveMapDiffer->doDiff(
			$this->toSenseDiffArray( $old ),
			$this->toSenseDiffArray( $new )
		);

		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			$old->getStatements(),
			$new->getStatements()
		);

		return new ChangeSenseDiffOp( $old->getId(), new Diff( $diffOps ) );
	}

	public function getAddSenseDiff( Sense $sense ) {
		$diffOps = $this->recursiveMapDiffer->doDiff(
			[],
			$this->toSenseDiffArray( $sense )
		);
		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			new StatementList(),
			$sense->getStatements()
		);

		return new AddSenseDiff( $sense, new Diff( $diffOps ) );
	}

	public function getRemoveSenseDiff( Sense $sense ) {
		$diffOps = $this->recursiveMapDiffer->doDiff(
			$this->toSenseDiffArray( $sense ),
			[]
		);
		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			$sense->getStatements(),
			new StatementList()
		);

		return new RemoveSenseDiff( $sense->getId(), new Diff( $diffOps ) );
	}

	/**
	 * @param Sense $sense
	 *
	 * @return string[][]
	 */
	private function toSenseDiffArray( Sense $sense ) {
		$result = [];
		$result['glosses'] = $sense->getGlosses()->toTextArray();

		return $result;
	}

}
