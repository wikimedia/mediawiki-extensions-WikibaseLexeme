<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use MWException;
use PermissionsError;
use UnexpectedValueException;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
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
	 * @throws \DomainException
	 */
	public function assignFreshId( EntityDocument $form ) {
		if ( $form instanceof BlankForm ) {
			return;
		}

		throw new \DomainException( 'Form IDs are currently assigned in Lexeme::addOrUpdateForm()' );
	}

	/**
	 * @see EntityStore::saveEntity
	 *
	 * @param Form $form
	 * @param string $summary
	 * @param User $user
	 * @param int $flags
	 * @param int|bool $baseRevId
	 * @param string[] $tags
	 *
	 * @throws StorageException
	 * @throws PermissionsError
	 * @return EntityRevision
	 */
	public function saveEntity(
		EntityDocument $form,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false,
		array $tags = []
	) {
		Assert::parameterType( Form::class, $form, '$form' );

		// EntityRevisionLookup and EntityStore have different opinions on valid revId fallbacks
		$getLexemeRevId = 0;
		if ( is_int( $baseRevId ) ) {
			$getLexemeRevId = $baseRevId;
		}

		$formId = $form->getId();
		$revision = $this->getLexemeRevision( $formId, $getLexemeRevId );
		/** @var Lexeme $lexeme */
		$lexeme = $revision->getEntity();
		'@phan-var Lexeme $lexeme';

		$lexeme->addOrUpdateForm( $form );

		// Unset EDIT_NEW flag if present (forms don't have their own pages, thus EDIT_NEW is never needed)
		$flags &= ~EDIT_NEW;

		return $this->store->saveEntity( $lexeme, $summary, $user, $flags, $baseRevId, $tags );
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
	 * @return never
	 */
	public function saveRedirect(
		EntityRedirect $redirect,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false,
		array $tags = []
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
	 * @throws StorageException
	 * @throws PermissionsError
	 */
	public function deleteEntity( EntityId $formId, $reason, User $user ) {
		Assert::parameterType( FormId::class, $formId, '$formId' );
		/** @var Lexeme $lexeme */
		$lexeme = $this->getLexemeRevision( $formId )->getEntity();
		'@phan-var Lexeme $lexeme';
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
		Assert::parameterType( FormId::class, $formId, '$formId' );

		return $this->store->userWasLastToEdit( $user, $formId->getLexemeId(), $lastRevId );
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
		Assert::parameterType( FormId::class, $formId, '$formId' );

		$this->store->updateWatchlist( $user, $formId->getLexemeId(), $watch );
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
		Assert::parameterType( FormId::class, $formId, '$formId' );

		return $this->store->isWatching( $user, $formId->getLexemeId() );
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
	 * @param FormId $formId
	 * @param int $revisionId
	 *
	 * @throws StorageException
	 * @return EntityRevision guaranteed to contain a Lexeme
	 */
	private function getLexemeRevision( FormId $formId, $revisionId = 0 ) {

		if ( !is_int( $revisionId ) ) {
			throw new UnexpectedValueException(
				'EntityRevisionLookup does not accept non-int revision ids!'
			);
		}

		$revision = $this->lookup->getEntityRevision(
			$formId->getLexemeId(),
			$revisionId,
			LookupConstants::LATEST_FROM_MASTER
		);

		if ( !$revision || !( $revision->getEntity() instanceof Lexeme ) ) {
			throw new StorageException( 'Cannot resolve ' . $formId->getSerialization() );
		}

		return $revision;
	}

}
