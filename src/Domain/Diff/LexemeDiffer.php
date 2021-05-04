<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\Comparer\CallbackComparer;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use InvalidArgumentException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDifferStrategy;
use Wikibase\DataModel\Services\Diff\StatementListDiffer;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class LexemeDiffer implements EntityDifferStrategy {

	/**
	 * @var StatementListDiffer
	 */
	private $statementListDiffer;

	/**
	 * @var MapDiffer
	 */
	private $recursiveMapDiffer;

	/**
	 * @var FormDiffer
	 */
	private $formDiffer;

	/**
	 * @var SenseDiffer
	 */
	private $senseDiffer;

	/**
	 * @var MapDiffer
	 */
	private $itemIdDiffer;

	public function __construct() {
		$this->recursiveMapDiffer = new MapDiffer( true );
		$this->itemIdDiffer = new MapDiffer( false, null, new ComparableComparer() );
		$this->statementListDiffer = new StatementListDiffer();
		$this->formDiffer = new FormDiffer();
		$this->senseDiffer = new SenseDiffer();
	}

	/**
	 * @param string $entityType
	 *
	 * @return bool
	 */
	public function canDiffEntityType( $entityType ) {
		return $entityType === Lexeme::ENTITY_TYPE;
	}

	/**
	 * @param Lexeme $from
	 * @param Lexeme $to
	 *
	 * @return EntityDiff
	 *
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		Assert::parameterType( Lexeme::class, $from, '$from' );
		Assert::parameterType( Lexeme::class, $to, '$to' );

		return $this->diffLexemes( $from, $to );
	}

	/**
	 * @param Lexeme $from
	 * @param Lexeme $to
	 *
	 * @return EntityDiff
	 */
	public function diffLexemes( Lexeme $from, Lexeme $to ) {
		$diffOps = $this->recursiveMapDiffer->doDiff(
			$this->toDiffArray( $from ),
			$this->toDiffArray( $to )
		);

		$diffOps['lexicalCategory'] = new Diff( $this->itemIdDiffer->doDiff(
			$this->getLexicalCategoryAsArray( $from ),
			$this->getLexicalCategoryAsArray( $to )
		) );
		$diffOps['language'] = new Diff( $this->itemIdDiffer->doDiff(
			$this->getLanguageAsArray( $from ),
			$this->getLanguageAsArray( $to )
		) );

		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			$from->getStatements(),
			$to->getStatements()
		);

		$diffOps['nextFormId'] = $this->getNextFormIdCounterDiff( $from, $to );

		$diffOps['forms'] = $this->getFormsDiff(
			$from->getForms(),
			$to->getForms()
		);

		$diffOps['senses'] = $this->getSensesDiff(
			$from->getSenses(),
			$to->getSenses()
		);

		$diffOps['nextSenseId'] = $this->getNextSenseIdCounterDiff( $from, $to );

		return new LexemeDiff( $diffOps );
	}

	/**
	 * @param Lexeme $lexeme
	 *
	 * @return array[]
	 */
	private function toDiffArray( Lexeme $lexeme ) {
		$array = [];
		$lemmas = $lexeme->getLemmas();

		$array['lemmas'] = $lemmas->toTextArray();

		return $array;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 *
	 * @throws InvalidArgumentException
	 */
	public function getConstructionDiff( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		return $this->diffEntities( new Lexeme(), $entity );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 *
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		return $this->diffEntities( $entity, new Lexeme() );
	}

	/**
	 * @param FormSet $from
	 * @param FormSet $to
	 *
	 * @return Diff
	 */
	private function getFormsDiff( FormSet $from, FormSet $to ) {
		$differ = new MapDiffer(
			false,
			null,
			new CallbackComparer(
				static function ( Form $from, Form $to ) {
					return $from == $to;
				}
			)
		);

		$from = $this->toFormsDiffArray( $from );
		$to = $this->toFormsDiffArray( $to );
		$formDiffOps = $differ->doDiff( $from, $to );

		foreach ( $formDiffOps as $index => $formDiffOp ) {
			if ( $formDiffOp instanceof DiffOpChange ) {
				/** @var DiffOpChange $formDiffOp */
				$formDiffOps[$index] = $this->formDiffer->diff(
					$formDiffOp->getOldValue(),
					$formDiffOp->getNewValue()
				);
			}
			if ( $formDiffOp instanceof DiffOpAdd ) {
				$formDiffOps[$index] = $this->formDiffer->getAddFormDiff( $formDiffOp->getNewValue() );
			}
			if ( $formDiffOp instanceof DiffOpRemove ) {
				$formDiffOps[$index] = $this->formDiffer->getRemoveFormDiff( $formDiffOp->getOldValue() );
			}
		}

		return new Diff( $formDiffOps, true );
	}

	/**
	 * @param FormSet $forms
	 *
	 * @return Form[]
	 */
	private function toFormsDiffArray( FormSet $forms ) {
		$result = [];

		foreach ( $forms->toArray() as $form ) {
			$result[$form->getId()->getSerialization()] = $form;
		}

		return $result;
	}

	private function getNextFormIdCounterDiff( Lexeme $from, Lexeme $to ) {
		if ( $to->getNextFormId() <= $from->getNextFormId() ) {
			return new Diff( [] );
		}

		return new Diff( [ new DiffOpChange( $from->getNextFormId(), $to->getNextFormId() ) ] );
	}

	/**
	 * @param SenseSet $from
	 * @param SenseSet $to
	 *
	 * @return Diff
	 */
	private function getSensesDiff( SenseSet $from, SenseSet $to ) {
		$differ = new MapDiffer(
			false,
			null,
			new CallbackComparer(
				static function ( Sense $from, Sense $to ) {
					return $from == $to;
				}
			)
		);

		$from = $this->toSensesDiffArray( $from );
		$to = $this->toSensesDiffArray( $to );
		$senseDiffOps = $differ->doDiff( $from, $to );

		foreach ( $senseDiffOps as $index => $senseDiffOp ) {
			if ( $senseDiffOp instanceof DiffOpChange ) {
				/** @var DiffOpChange $senseDiffOp */
				$senseDiffOps[$index] = $this->senseDiffer->diffEntities(
					$senseDiffOp->getOldValue(),
					$senseDiffOp->getNewValue()
				);
			}
			if ( $senseDiffOp instanceof DiffOpAdd ) {
				$senseDiffOps[$index] = $this->senseDiffer->getAddSenseDiff( $senseDiffOp->getNewValue() );
			}
			if ( $senseDiffOp instanceof DiffOpRemove ) {
				$senseDiffOps[$index] = $this->senseDiffer->getRemoveSenseDiff( $senseDiffOp->getOldValue() );
			}
		}

		return new Diff( $senseDiffOps, true );
	}

	/**
	 * @param SenseSet $senses
	 *
	 * @return Sense[]
	 */
	private function toSensesDiffArray( SenseSet $senses ) {
		$result = [];

		foreach ( $senses->toArray() as $sense ) {
			$result[$sense->getId()->getSerialization()] = $sense;
		}

		return $result;
	}

	private function getNextSenseIdCounterDiff( Lexeme $from, Lexeme $to ) {
		if ( $to->getNextSenseId() <= $from->getNextSenseId() ) {
			return new Diff( [] );
		}

		return new Diff( [ new DiffOpChange( $from->getNextSenseId(), $to->getNextSenseId() ) ] );
	}

	private function getLexicalCategoryAsArray( Lexeme $lexeme ) {
		try {
			return [ 'id' => $lexeme->getLexicalCategory() ];
		} catch ( UnexpectedValueException $ex ) {
			return []; // It's fine to skip uninitialized properties in a diff
		}
	}

	private function getLanguageAsArray( Lexeme $lexeme ) {
		try {
			return [ 'id' => $lexeme->getLanguage() ];
		} catch ( UnexpectedValueException $ex ) {
			return []; // It's fine to skip uninitialized properties in a diff
		}
	}

}
