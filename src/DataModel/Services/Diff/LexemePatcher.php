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
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemePatchAccess;
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

	/**
	 * @var FormPatcher
	 */
	private $formPatcher;

	public function __construct() {
		$this->termListPatcher = new TermListPatcher();
		$this->statementListPatcher = new StatementListPatcher();
		$this->formPatcher = new FormPatcher();
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
		Assert::parameterType( LexemeDiff::class, $patch, '$patch' );
		/** @var Lexeme $entity */
		/** @var LexemeDiff $patch */

		//TODO Lemmas can't be null. Redundant check
		$lemmas = $entity->getLemmas() !== null ? $entity->getLemmas() : new TermList();
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

		$this->patchNextFormId( $entity, $patch );

		$this->patchForms( $entity, $patch );
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

	private function patchNextFormId( Lexeme $entity, LexemeDiff $patch ) {
		// FIXME: Why is this a loop? The nextFormId field is not an array!
		foreach ( $patch->getNextFormIdDiff() as $nextFormIdDiff ) {
			if ( !( $nextFormIdDiff instanceof DiffOpChange ) ) {
				throw new PatcherException( 'Invalid forms list diff' );
			}

			$newNumber = $nextFormIdDiff->getNewValue();
			if ( $newNumber > $entity->getNextFormId() ) {
				$entity->patch( function ( LexemePatchAccess $patchAccess ) use ( $newNumber ) {
					$patchAccess->increaseNextFormIdTo( $newNumber );
				} );
			}
		}
	}

	private function patchForms( Lexeme $lexeme, LexemeDiff $patch ) {
		$formsDiff = $patch->getFormsDiff();
		foreach ( $formsDiff as $formDiff ) {
			switch ( true ) {
				case $formDiff instanceof DiffOpAdd:
					/** @var DiffOpAdd $formDiff */
					/** @var Form $form */
					$form = $formDiff->getNewValue();
					$lexeme->patch(
						function ( LexemePatchAccess $patchAccess ) use ( $form ) {
							$patchAccess->addForm( $form );
						}
					);
					break;

				case $formDiff instanceof DiffOpRemove:
					/** @var DiffOpRemove $formDiff */
					/** @var Form $form */
					$form = $formDiff->getOldValue();
					$lexeme->removeForm( $form->getId() );
					break;

				case $formDiff instanceof ChangeFormDiffOp:
					/** @var ChangeFormDiffOp $formDiff */
					/** @var Form $form */
					if ( $lexeme->hasForm( $formDiff->getFormId() ) ) {
						$form = $lexeme->getForm( $formDiff->getFormId() );
						$this->formPatcher->patch( $form, $formDiff );
					}
					break;

				default:
					throw new PatcherException( 'Invalid forms list diff: ' . get_class( $formDiff ) );
			}
		}
	}

}
