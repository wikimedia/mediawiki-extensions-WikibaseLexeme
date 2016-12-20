<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Diff\Patcher\PatcherException;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityPatcherStrategy;
use Wikibase\DataModel\Services\Diff\StatementListPatcher;
use Wikibase\DataModel\Services\Diff\TermListPatcher;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Mättig
 */
class LexemePatcher implements EntityPatcherStrategy {

	/**
	 * @var TermListPatcher
	 */
	private $termListPatcher;

	/**
	 * @var StatementListPatcher
	 */
	private $statementListPatcher;

	public function __construct() {
		$this->termListPatcher = new TermListPatcher();
		$this->statementListPatcher = new StatementListPatcher();
	}

	/**
	 * @param string $entityType
	 *
	 * @return bool
	 */
	public function canPatchEntityType( $entityType ) {
		return $entityType === Lexeme::ENTITY_TYPE;
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */
		/** @var LexemeDiff $patch */
		$lemmas = !is_null( $entity->getLemmas() ) ? $entity->getLemmas() : new TermList();
		$this->termListPatcher->patchTermList(
			$lemmas,
			$patch->getLemmasDiff()
		);

		/** @var Lexeme $entity */
		$this->statementListPatcher->patchStatementList(
			$entity->getStatements(),
			$patch->getClaimsDiff()
		);

		$itemId = $this->getPatchedItemId( $patch->getLexicalCategoryDiff() );
		if ( $itemId !== false ) {
			$entity->setLexicalCategory( $itemId );
		}

		$itemId = $this->getPatchedItemId( $patch->getLanguageDiff() );
		if ( $itemId !== false ) {
			$entity->setLanguage( $itemId );
		}
	}

	/**
	 * @param Diff $patch
	 *
	 * @throws PatcherException
	 * @return ItemId|null|false False in case the diff is valid, but does not contain a change.
	 */
	private function getPatchedItemId( Diff $patch ) {
		if ( $patch->isEmpty() ) {
			return false;
		}

		$diffOp = $patch['id'];

		switch ( true ) {
			case $diffOp instanceof DiffOpAdd:
				/** @var DiffOpAdd $diffOp */
				return new ItemId( $diffOp->getNewValue() );

			case $diffOp instanceof DiffOpChange:
				/** @var DiffOpChange $diffOp */
				return new ItemId( $diffOp->getNewValue() );

			case $diffOp instanceof DiffOpRemove:
				return null;
		}

		throw new PatcherException( 'Invalid ItemId diff' );
	}

}
