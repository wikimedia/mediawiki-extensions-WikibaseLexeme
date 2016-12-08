<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\Differ\MapDiffer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDifferStrategy;
use Wikibase\DataModel\Services\Diff\StatementListDiffer;
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
	 * LexemeDiffer constructor.
	 */
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

		if ( !is_null( $lemmas ) ) {
			$array['lemmas'] = $lemmas->toTextArray();
		}

		$lexicalCategory = $lexeme->getLexicalCategory();
		if ( !is_null( $lexicalCategory ) ) {
			$array['lexicalCategory'] = [ 'id' => $lexicalCategory->getSerialization() ];
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

}
