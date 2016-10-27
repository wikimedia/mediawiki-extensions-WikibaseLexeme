<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

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

	public function __construct() {
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

		return $this->diffLexemes( $from, $to );
	}

	/**
	 * @param Lexeme $from
	 * @param Lexeme $to
	 *
	 * @return EntityDiff
	 */
	public function diffLexemes( Lexeme $from, Lexeme $to ) {
		return new EntityDiff( [
			'claim' => $this->statementListDiffer->getDiff(
				$from->getStatements(),
				$to->getStatements()
			),
		] );
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
