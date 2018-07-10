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
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemePatchAccess;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Kreuz
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
	 * @param Lexeme $lexeme
	 * @param LexemeDiff $patch
	 *
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $lexeme, EntityDiff $patch ) {
		Assert::parameterType( Lexeme::class, $lexeme, '$lexeme' );
		Assert::parameterType( LexemeDiff::class, $patch, '$patch' );

		$this->termListPatcher->patchTermList( $lexeme->getLemmas(), $patch->getLemmasDiff() );

		$this->statementListPatcher->patchStatementList(
			$lexeme->getStatements(),
			$patch->getClaimsDiff()
		);

		$itemId = $this->getPatchedItemId( $patch->getLexicalCategoryDiff() );
		if ( $itemId !== false ) {
			$lexeme->setLexicalCategory( $itemId );
		}

		$itemId = $this->getPatchedItemId( $patch->getLanguageDiff() );
		if ( $itemId !== false ) {
			$lexeme->setLanguage( $itemId );
		}

		$this->patchNextFormId( $lexeme, $patch );

		$this->patchForms( $lexeme, $patch );

		// TODO patch next sense ID and senses
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
				return $diffOp->getNewValue();

			case $diffOp instanceof DiffOpChange:
				return $diffOp->getNewValue();

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
		foreach ( $patch->getFormsDiff() as $formDiff ) {
			switch ( true ) {
				case $formDiff instanceof AddFormDiff:
					$form = $formDiff->getAddedForm();
					$lexeme->patch(
						function ( LexemePatchAccess $patchAccess ) use ( $form ) {
							$patchAccess->addForm( $form );
						}
					);
					break;

				case $formDiff instanceof RemoveFormDiff:
					$lexeme->removeForm( $formDiff->getRemovedFormId() );
					break;

				case $formDiff instanceof ChangeFormDiffOp:
					$form = $lexeme->getForm( $formDiff->getFormId() );
					if ( $form !== null ) {
						$this->formPatcher->patch( $form, $formDiff );
					}
					break;

				default:
					throw new PatcherException( 'Invalid forms list diff: ' . get_class( $formDiff ) );
			}
		}
	}

}
