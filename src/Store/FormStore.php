<?php

namespace Wikibase\Lexeme\Store;

use MWException;
use PermissionsError;
use UnexpectedValueException;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class FormStore implements EntityStore {

	/**
	 * @var EntityStore
	 */
	private $store;

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	public function __construct( EntityStore $store, EntityRevisionLookup $lookup ) {
		$this->store = $store;
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityStore::assignFreshId
	 *
	 * @param Form $form
	 *
	 * @throws \DomainException always
	 */
	public function assignFreshId( EntityDocument $form ) {
		throw new \DomainException( 'Form IDs are currently assigned in Lexeme::addForm' );
	}

	/**
	 * @see EntityStore::saveEntity
	 *
	 * @param Form $form
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param int|bool $baseRevId
	 *
	 * @throws UnexpectedValueException
	 * @throws StorageException
	 * @throws PermissionsError
	 * @return EntityRevision
	 */
	public function saveEntity(
		EntityDocument $form,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false
	) {
		$formId = $form->getId();
		$revision = $this->getLexemeRevision( $formId, $baseRevId );
		/** @var Lexeme $lexeme */
		$lexeme = $revision->getEntity();

		// FIXME: This will change the position of the Form in the set, but shouldn't.
		$lexeme->removeForm( $formId );
		$lexeme->getForms()->add( $form );

		return $this->store->saveEntity( $lexeme, $summary, $user, $flags, $baseRevId );
	}

	/**
	 * @see EntityStore::saveRedirect
	 *
	 * @param EntityRedirect $redirect
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param int|bool $baseRevId
	 *
	 * @throws \DomainException always
	 * @return int
	 */
	public function saveRedirect(
		EntityRedirect $redirect,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false
	) {
		throw new \DomainException( 'Forms currently don\'t support redirects' );
	}

	/**
	 * @see EntityStore::deleteEntity
	 *
	 * @param FormId $formId
	 * @param string $reason
	 * @param User $user
	 *
	 * @throws UnexpectedValueException
	 */
	public function deleteEntity( EntityId $formId, $reason, User $user ) {
		/** @var Lexeme $lexeme */
		$lexeme = $this->getLexemeRevision( $formId )->getEntity();
		$lexeme->removeForm( $formId );
		$this->store->saveEntity( $lexeme, $reason, $user, EDIT_UPDATE );
	}

	/**
	 * @see EntityStore::userWasLastToEdit
	 *
	 * @param User $user
	 * @param FormId $formId
	 * @param int $lastRevId
	 *
	 * @throws UnexpectedValueException
	 * @return bool
	 */
	public function userWasLastToEdit( User $user, EntityId $formId, $lastRevId ) {
		return $this->store->userWasLastToEdit( $user, $this->getLexemeId( $formId ), $lastRevId );
	}

	/**
	 * @see EntityStore::updateWatchlist
	 *
	 * @param User $user
	 * @param FormId $formId
	 * @param bool $watch
	 *
	 * @throws UnexpectedValueException
	 * @throws MWException
	 */
	public function updateWatchlist( User $user, EntityId $formId, $watch ) {
		$this->store->updateWatchlist( $user, $this->getLexemeId( $formId ), $watch );
	}

	/**
	 * @see EntityStore::isWatching
	 *
	 * @param User $user
	 * @param FormId $formId
	 *
	 * @throws UnexpectedValueException
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $formId ) {
		return $this->store->isWatching( $user, $this->getLexemeId( $formId ) );
	}

	/**
	 * @see EntityStore::canCreateWithCustomId
	 *
	 * @param FormId $formId
	 *
	 * @return bool
	 */
	public function canCreateWithCustomId( EntityId $formId ) {
		return false;
	}

	/**
	 * TODO: Move to the Form class.
	 *
	 * @param FormId $formId
	 *
	 * @return LexemeId
	 */
	private function getLexemeId( EntityId $formId ) {
		if ( !( $formId instanceof FormId ) ) {
			throw new UnexpectedValueException( '$formId must be a FormId' );
		}

		$parts = EntityId::splitSerialization( $formId->getLocalPart() );
		$parts = explode( '-', $parts[2], 2 );
		return new LexemeId( $parts[0] );
	}

	/**
	 * @param FormId $formId
	 * @param int $revisionId
	 *
	 * @throws StorageException
	 * @return EntityRevision guaranteed to contain a Lexeme
	 */
	private function getLexemeRevision( EntityId $formId, $revisionId = 0 ) {
		$revision = $this->lookup->getEntityRevision(
			$this->getLexemeId( $formId ),
			$revisionId,
			EntityRevisionLookup::LATEST_FROM_MASTER
		);

		if ( !$revision || !( $revision->getEntity() instanceof Lexeme ) ) {
			throw new StorageException( 'Cannot resolve ' . $formId->getSerialization() );
		}

		return $revision;
	}

}
