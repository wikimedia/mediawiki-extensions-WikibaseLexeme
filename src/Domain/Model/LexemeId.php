<?php

namespace Wikibase\Lexeme\Domain\Model;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\SerializableEntityId;
use Wikimedia\Assert\Assert;

/**
 * Immutable ID of a Lexeme in the lexicographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Lexeme
 *
 * @license GPL-2.0-or-later
 */
class LexemeId extends SerializableEntityId implements Int32EntityId {

	public const PATTERN = '/^L[1-9]\d{0,9}\z/i';

	/**
	 * @param string $serialization
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $serialization ) {
		$this->assertValidIdFormat( $serialization );
		parent::__construct( strtoupper( $serialization ) );
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

	public function __serialize(): array {
		return [ 'serialization' => $this->serialization ];
	}

	public function __unserialize( array $data ): void {
		$this->serialization = $data['serialization'] ?? '';
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
	 */
	public function getNumericId() {
		return (int)substr( $this->serialization, 1 );
	}

}
