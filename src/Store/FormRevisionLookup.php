<?php

namespace Wikibase\Lexeme\Store;

use OutOfRangeException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataTransfer\NullFormId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class FormRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	public function __construct( EntityRevisionLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @param FormId $formId
	 * @param int $revisionId
	 * @param string $mode
	 *
	 * @throws UnexpectedValueException
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $formId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_REPLICA
	) {
		Assert::parameterType( FormId::class, $formId, '$formId' );

		if ( $formId instanceof NullFormId ) {
			return null;
		}

		$revision = $this->lookup->getEntityRevision( $formId->getLexemeId(), $revisionId, $mode );
		if ( $revision === null ) {
			return null;
		}

		/** @var Lexeme $lexeme */
		$lexeme = $revision->getEntity();

		try {
			// TODO use hasForm on Lexeme or FormSet when it exists
			$form = $lexeme->getForm( $formId );
		} catch ( OutOfRangeException $ex ) {
			return null;
		}

		return new EntityRevision( $form, $revision->getRevisionId(), $revision->getTimestamp() );
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param FormId $formId
	 * @param string $mode
	 *
	 * @throws UnexpectedValueException
	 * @return LatestRevisionIdResult|int|false
	 */
	public function getLatestRevisionId( EntityId $formId, $mode = self::LATEST_FROM_REPLICA ) {
		Assert::parameterType( FormId::class, $formId, '$formId' );

		$lexemeId = $formId->getLexemeId();

		$revisionId = $this->lookup->getLatestRevisionId( $lexemeId, $mode );
		if (
			class_exists( LatestRevisionIdResult::class )
			&& is_object( $revisionId )
			&& $revisionId instanceof LatestRevisionIdResult ) {
			return $this->handleNewResult( $formId, $mode, $revisionId, $lexemeId );
		}

		//TODO Remove everything below once patch in Wikibase is merged
		if ( $revisionId === false ) {
			return false;
		}

		$revision = $this->lookup->getEntityRevision( $lexemeId, $revisionId, $mode );
		/** @var Lexeme $lexeme */
		$lexeme = $revision->getEntity();

		try {
			$lexeme->getForm( $formId );
		} catch ( OutOfRangeException $ex ) {
			return false;
		}

		return $revisionId;
	}

	/**
	 * @param FormId $formId
	 * @param string $mode
	 * @param LatestRevisionIdResult $revisionIdResult
	 * @param LexemeId $lexemeId
	 * @return LatestRevisionIdResult
	 */
	private function handleNewResult(
		FormId $formId,
		$mode,
		LatestRevisionIdResult $revisionIdResult,
		LexemeId $lexemeId
	) {
		$returnNonexistentEntityResult = function () {
			return LatestRevisionIdResult::nonexistentEntity();
		};

		return $revisionIdResult->onRedirect( $returnNonexistentEntityResult )
			->onNonexistentEntity( $returnNonexistentEntityResult )
			->onConcreteRevision(
				function ( $revisionId ) use ( $lexemeId, $mode, $formId ) {
					$revision = $this->lookup->getEntityRevision( $lexemeId, $revisionId, $mode );
					/** @var Lexeme $lexeme */
					$lexeme = $revision->getEntity();

					try {
						$lexeme->getForm( $formId );
					} catch ( OutOfRangeException $ex ) {
						return LatestRevisionIdResult::nonexistentEntity();
					}

					return LatestRevisionIdResult::concreteRevision( $revisionId );
				}
			)
			->map();
	}

}
