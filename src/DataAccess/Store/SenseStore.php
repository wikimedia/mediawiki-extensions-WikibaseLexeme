<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use MWException;
use PermissionsError;
use UnexpectedValueException;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class SenseStore implements EntityStore {

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
	 * @param Sense $sense
	 *
	 * @throws \DomainException
	 */
	public function assignFreshId( EntityDocument $sense ) {
		if ( $sense instanceof BlankSense ) {
			return;
		}

		throw new \DomainException( 'Sense IDs are currently assigned in Lexeme::addOrUpdateSense()' );
	}

	/**
	 * @see EntityStore::saveEntity
	 *
	 * @param Sense $sense
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
		EntityDocument $sense,
		$summary,
		User $user,
		$flags = 0,
		$baseRevId = false,
		array $tags = []
	) {
		Assert::parameterType( Sense::class, $sense, '$sense' );

		// EntityRevisionLookup and EntityStore have different opinions on valid revId fallbacks
		$getLexemeRevId = 0;
		if ( is_int( $baseRevId ) ) {
			$getLexemeRevId = $baseRevId;
		}

		$senseId = $sense->getId();
		$revision = $this->getLexemeRevision( $senseId, $getLexemeRevId );
		/** @var Lexeme $lexeme */
		$lexeme = $revision->getEntity();
		'@phan-var Lexeme $lexeme';

		$lexeme->addOrUpdateSense( $sense );

		// Unset EDIT_NEW flag if present (senses don't have own pages, thus EDIT_NEW is never needed)
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
		throw new \DomainException( 'Senses currently don\'t support redirects' );
	}

	/**
	 * @see EntityStore::deleteEntity
	 *
	 * @param SenseId $senseId
	 * @param string $reason
	 * @param User $user
	 *
	 * @throws StorageException
	 * @throws PermissionsError
	 */
	public function deleteEntity( EntityId $senseId, $reason, User $user ) {
		Assert::parameterType( SenseId::class, $senseId, '$senseId' );
		/** @var Lexeme $lexeme */
		$lexeme = $this->getLexemeRevision( $senseId )->getEntity();
		'@phan-var Lexeme $lexeme';
		$lexeme->removeSense( $senseId );
		$this->store->saveEntity( $lexeme, $reason, $user, EDIT_UPDATE );
	}

	/**
	 * @see EntityStore::userWasLastToEdit
	 *
	 * @param User $user
	 * @param SenseId $senseId
	 * @param int $lastRevId
	 *
	 * @throws UnexpectedValueException
	 * @return bool
	 */
	public function userWasLastToEdit( User $user, EntityId $senseId, $lastRevId ) {
		Assert::parameterType( SenseId::class, $senseId, '$senseId' );

		return $this->store->userWasLastToEdit( $user, $senseId->getLexemeId(), $lastRevId );
	}

	/**
	 * @see EntityStore::updateWatchlist
	 *
	 * @param User $user
	 * @param SenseId $senseId
	 * @param bool $watch
	 *
	 * @throws UnexpectedValueException
	 * @throws MWException
	 */
	public function updateWatchlist( User $user, EntityId $senseId, $watch ) {
		Assert::parameterType( SenseId::class, $senseId, '$senseId' );

		$this->store->updateWatchlist( $user, $senseId->getLexemeId(), $watch );
	}

	/**
	 * @see EntityStore::isWatching
	 *
	 * @param User $user
	 * @param SenseId $senseId
	 *
	 * @throws UnexpectedValueException
	 * @return bool
	 */
	public function isWatching( User $user, EntityId $senseId ) {
		Assert::parameterType( SenseId::class, $senseId, '$senseId' );

		return $this->store->isWatching( $user, $senseId->getLexemeId() );
	}

	/**
	 * @see EntityStore::canCreateWithCustomId
	 *
	 * @param SenseId $senseId
	 *
	 * @return bool
	 */
	public function canCreateWithCustomId( EntityId $senseId ) {
		return false;
	}

	/**
	 * @param SenseId $senseId
	 * @param int $revisionId
	 *
	 * @throws StorageException
	 * @return EntityRevision guaranteed to contain a Lexeme
	 */
	private function getLexemeRevision( SenseId $senseId, $revisionId = 0 ) {

		if ( !is_int( $revisionId ) ) {
			throw new UnexpectedValueException(
				'EntityRevisionLookup does not accept non-int revision ids!'
			);
		}

		$revision = $this->lookup->getEntityRevision(
			$senseId->getLexemeId(),
			$revisionId,
			LookupConstants::LATEST_FROM_MASTER
		);

		if ( !$revision || !( $revision->getEntity() instanceof Lexeme ) ) {
			throw new StorageException( 'Cannot resolve ' . $senseId->getSerialization() );
		}

		return $revision;
	}

}
