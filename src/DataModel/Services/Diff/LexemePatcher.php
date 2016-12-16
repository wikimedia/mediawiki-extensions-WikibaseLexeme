<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
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

		$this->patchLexicalCategory(
			$entity,
			$patch->getLexicalCategoryDiff()
		);

		$this->patchLanguage(
			$entity,
			$patch->getLanguageDiff()
		);
	}

	private function patchLexicalCategory( Lexeme $lexeme, Diff $patch ) {
		/** @var DiffOp $diffOp */
		foreach ( $patch as $diffOp ) {
			switch ( true ) {
				case $diffOp instanceof DiffOpAdd:
					/** @var DiffOpAdd $diffOp */
					$lexeme->setLexicalCategory(
						new ItemId( $diffOp->getNewValue() )
					);
					break;

				case $diffOp instanceof DiffOpChange:
					/** @var DiffOpAdd $diffOp */
					$lexeme->setLexicalCategory(
						new ItemId( $diffOp->getNewValue() )
					);
					break;

				case $diffOp instanceof DiffOpRemove:
					/** @var DiffOpRemove $diffOp */
					$lexeme->setLexicalCategory( null );
					break;

				default:
					throw new PatcherException( 'Invalid lexical category diff' );
			}
		}
	}

	private function patchLanguage( Lexeme $lexeme, Diff $patch ) {
		/** @var DiffOp $diffOp */
		foreach ( $patch as $diffOp ) {
			switch ( true ) {
				case $diffOp instanceof DiffOpAdd:
					/** @var DiffOpAdd $diffOp */
					$lexeme->setLanguage(
						new ItemId( $diffOp->getNewValue() )
					);
					break;

				case $diffOp instanceof DiffOpChange:
					/** @var DiffOpAdd $diffOp */
					$lexeme->setLanguage(
						new ItemId( $diffOp->getNewValue() )
					);
					break;

				case $diffOp instanceof DiffOpRemove:
					/** @var DiffOpRemove $diffOp */
					$lexeme->setLanguage( null );
					break;

				default:
					throw new PatcherException( 'Invalid language diff' );
			}
		}
	}

}
