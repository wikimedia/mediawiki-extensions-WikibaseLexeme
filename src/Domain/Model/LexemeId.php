<?php

declare( strict_types = 1 );

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
	 * @throws InvalidArgumentException
	 */
	public function __construct( string $serialization ) {
		$this->assertValidIdFormat( $serialization );
		parent::__construct( strtoupper( $serialization ) );
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function assertValidIdFormat( string $serialization ): void {
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
		$this->__construct( $data['serialization'] ?? '' );
		if ( $this->serialization !== $data['serialization'] ) {
			throw new InvalidArgumentException( '$data contained invalid serialization' );
		}
	}

	public function getEntityType(): string {
		return 'lexeme';
	}

	public function getNumericId(): int {
		return (int)substr( $this->serialization, 1 );
	}

}
