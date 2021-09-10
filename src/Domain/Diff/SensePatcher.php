<?php

namespace Wikibase\Lexeme\Domain\Diff;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityPatcherStrategy;
use Wikibase\DataModel\Services\Diff\StatementListPatcher;
use Wikibase\DataModel\Services\Diff\TermListPatcher;
use Wikibase\Lexeme\Domain\Model\Sense;

/**
 * @license GPL-2.0-or-later
 */
class SensePatcher implements EntityPatcherStrategy {

	private $termListPatcher;

	private $statementListPatcher;

	public function __construct() {
		$this->termListPatcher = new TermListPatcher();
		$this->statementListPatcher = new StatementListPatcher();
	}

	/**
	 * @param string $entityType
	 *
	 * @return boolean
	 */
	public function canPatchEntityType( $entityType ) {
		return $entityType === 'sense';
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		if ( !( $entity instanceof Sense ) ) {
			throw new InvalidArgumentException( 'Can only patch Senses' );
		}

		// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType
		return $this->patch( $entity, $patch );
	}

	/**
	 * @param Sense $sense
	 * @param ChangeSenseDiffOp $diff
	 */
	private function patch( Sense $sense, ChangeSenseDiffOp $diff ) {
		$this->termListPatcher->patchTermList(
			$sense->getGlosses(),
			$diff->getGlossesDiff()
		);

		$this->statementListPatcher->patchStatementList(
			$sense->getStatements(),
			$diff->getStatementsDiff()
		);
	}

}
