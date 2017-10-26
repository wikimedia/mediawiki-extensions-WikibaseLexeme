<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDifferStrategy;
use Wikibase\DataModel\Services\Diff\StatementListDiffer;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikimedia\Assert\Assert;
use InvalidArgumentException;

/**
 * @license GPL-2.0+
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

	public function __construct() {
		$this->recursiveMapDiffer = new MapDiffer( true );
		$this->statementListDiffer = new StatementListDiffer();
		$this->formDiffer = new FormDiffer();
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
	 * @param EntityDocument $from
	 * @param EntityDocument $to
	 *
	 * @return EntityDiff
	 *
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		Assert::parameterType( Lexeme::class, $from, '$from' );
		Assert::parameterType( Lexeme::class, $to, '$to' );

		/** @var Lexeme $from */
		/** @var Lexeme $to */
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

		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			$from->getStatements(),
			$to->getStatements()
		);

		$diffOps['nextFormId'] = $this->getNextFormIdCounterDiff( $from, $to );

		$diffOps['forms'] = $this->getFormsDiff(
			$from->getForms(),
			$to->getForms()
		);

		return new LexemeDiff( $diffOps );
	}

	/**
	 * @param Lexeme $lexeme
	 *
	 * @return string[]
	 */
	private function toDiffArray( Lexeme $lexeme ) {
		$array = [];
		$lemmas = $lexeme->getLemmas();

		if ( $lemmas !== null ) {
			$array['lemmas'] = $lemmas->toTextArray();
		}

		try {
			$array['lexicalCategory'] = [ 'id' => $lexeme->getLexicalCategory()->getSerialization() ];
		} catch ( UnexpectedValueException $ex ) {
			// It's fine to skip uninitialized properties in a diff
		}

		try {
			$array['language'] = [ 'id' => $lexeme->getLanguage()->getSerialization() ];
		} catch ( UnexpectedValueException $ex ) {
			// It's fine to skip uninitialized properties in a diff
		}

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

		return $this->diffEntities( $entity, new Lexeme() );
	}

	/**
	 * @param FormSet $from
	 * @param FormSet $to
	 *
	 * @return Diff;
	 */
	private function getFormsDiff( FormSet $from, FormSet $to ) {
		$differ = new MapDiffer();

		$differ->setComparisonCallback( function ( Form $from, Form $to ) {
			return $from == $to;
		} );

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

}
