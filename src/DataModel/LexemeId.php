<?php

namespace Wikibase\Lexeme\DataModel;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikimedia\Assert\Assert;
use InvalidArgumentException;
use RuntimeException;

/**
 * Immutable ID of a Lexeme in the lexiographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Lexeme
 *
 * @license GPL-2.0+
 */
class LexemeId extends EntityId implements Int32EntityId {

	const PATTERN = '/^L[1-9]\d{0,9}\z/i';

	/**
	 * @param string $serialization
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $serialization ) {
		$serializationParts = self::splitSerialization( $serialization );
		$localId = strtoupper( $serializationParts[2] );
		$this->assertValidIdFormat( $localId );
		parent::__construct( self::joinSerialization(
			[ $serializationParts[0], $serializationParts[1], $localId ] )
		);
	}

	/**
	 * @param string $serialization
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertValidIdFormat( $serialization ) {
		Assert::parameterType( 'string', $serialization, '$serialization' );
		Assert::parameter(
			preg_match( self::PATTERN, $serialization ),
			'$serialization',
			'must match ' . self::PATTERN
		);
		Assert::parameter(
			strlen( $serialization ) <= 10 || substr( $serialization, 1 ) <= Int32EntityId::MAX,
			'$serialization',
			'must not exceed ' . Int32EntityId::MAX
		);
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize() {
		return $this->serialization;
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->serialization = $serialized;
		list( $this->repositoryName, $this->localPart ) = self::extractRepositoryNameAndLocalPart(
			$serialized
		);
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'lexeme';
	}

	/**
	 * @see Int32EntityId::getNumericId
	 *
	 * @return int
	 *
	 * @throws RuntimeException if called on a foreign ID.
	 */
	public function getNumericId() {
		if ( $this->isForeign() ) {
			throw new RuntimeException( 'getNumericId must not be called on foreign LexemeIds' );
		}
		return (int)substr( $this->serialization, 1 );
	}

}
