<?php

namespace Wikibase\Lexeme\Domain\Diff;

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
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemePatchAccess;
use Wikibase\Repo\WikibaseRepo;
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

	/**
	 * @var SensePatcher
	 */
	private $sensePatcher;

	public function __construct() {
		$this->termListPatcher = new TermListPatcher();
		$this->statementListPatcher = new StatementListPatcher();
		$this->formPatcher = new FormPatcher();
		$this->sensePatcher = new SensePatcher();
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

		$this->patchNextSenseId( $lexeme, $patch );
		$this->patchSenses( $lexeme, $patch );
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
				$entity->patch( static function ( LexemePatchAccess $patchAccess ) use ( $newNumber ) {
					$patchAccess->increaseNextFormIdTo( $newNumber );
				} );
			}
		}
	}

	private function patchNextSenseId( Lexeme $entity, LexemeDiff $patch ) {
		// FIXME: Same as above
		foreach ( $patch->getNextSenseIdDiff() as $nextSenseIdDiff ) {
			if ( !( $nextSenseIdDiff instanceof DiffOpChange ) ) {
				throw new PatcherException( 'Invalid senses list diff' );
			}

			$newNumber = $nextSenseIdDiff->getNewValue();
			if ( $newNumber > $entity->getNextSenseId() ) {
				$entity->patch( static function ( LexemePatchAccess $patchAccess ) use ( $newNumber ) {
					$patchAccess->increaseNextSenseIdTo( $newNumber );
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
						static function ( LexemePatchAccess $patchAccess ) use ( $form ) {
							$patchAccess->addForm( $form );
						}
					);
					break;

				case $formDiff instanceof RemoveFormDiff:
					$lexeme->removeForm( $formDiff->getRemovedFormId() );
					break;

				case $formDiff instanceof ChangeFormDiffOp:
					try {
						$form = $lexeme->getForm( $formDiff->getFormId() );
					} catch ( \OutOfRangeException $e ) {
						/**
						 * This should never happen, but somehow sometimes a request to remove a form ends up here
						 * for unknown reasons. See T326768.
						 *
						 * Log what data we have to hopefully help figure out the problem
						 */
						WikibaseRepo::getLogger()->warning( __METHOD__ . ': Form not found', [
							'formId' => $formDiff->getFormId(),
							'representationDiff' => $formDiff->getRepresentationDiff()->serialize(),
							'grammaticalFeaturesDiff' => $formDiff->getGrammaticalFeaturesDiff()->serialize(),
							'statementsDiff' => $formDiff->getStatementsDiff()->serialize(),
							'existingForms' => implode( ', ', array_map(
								fn( $form ) => $form->getId()->getSerialization(),
								$lexeme->getForms()->toArray()
							) ),
						] );

						throw $e;
					}
					$this->formPatcher->patch( $form, $formDiff );
					break;

				default:
					throw new PatcherException( 'Invalid forms list diff: ' . get_class( $formDiff ) );
			}
		}
	}

	private function patchSenses( Lexeme $lexeme, LexemeDiff $patch ) {
		foreach ( $patch->getSensesDiff() as $senseDiff ) {
			switch ( true ) {
				case $senseDiff instanceof AddSenseDiff:
					$sense = $senseDiff->getAddedSense();
					$lexeme->patch(
						static function ( LexemePatchAccess $patchAccess ) use ( $sense ) {
							$patchAccess->addSense( $sense );
						}
					);
					break;

				case $senseDiff instanceof RemoveSenseDiff:
					$lexeme->removeSense( $senseDiff->getRemovedSenseId() );
					break;

				case $senseDiff instanceof ChangeSenseDiffOp:
					$sense = $lexeme->getSense( $senseDiff->getSenseId() );
					if ( $sense !== null ) {
						$this->sensePatcher->patchEntity( $sense, $senseDiff );
					}
					break;

				default:
					throw new PatcherException( 'Invalid senses list diff: ' . get_class( $senseDiff ) );
			}
		}
	}

}
