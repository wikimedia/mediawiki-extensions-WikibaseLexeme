<?php

namespace Wikibase\Lexeme\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\Lexeme\Domain\Model\Lexeme;

/**
 * Serializer of Lexeme entities to be used to serializer entities for any external output
 * (i.e. API, Special pages, dumps etc).
 * For serialization to be used in the internal Wikibase storage layer
 * use {@link StorageLexemeSerializer} instead.
 *
 * @license GPL-2.0-or-later
 */
class ExternalLexemeSerializer implements DispatchableSerializer {

	/**
	 * @var StorageLexemeSerializer
	 */
	private $internalSerializer;

	public function __construct( StorageLexemeSerializer $internalSerializer ) {
		$this->internalSerializer = $internalSerializer;
	}

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Lexeme;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param Lexeme $object
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'ExternalLexemeSerializer can only serialize Lexeme objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Lexeme $lexeme ) {
		$internalSerialization = $this->internalSerializer->serialize( $lexeme );

		unset( $internalSerialization['nextFormId'] );
		unset( $internalSerialization['nextSenseId'] );

		return $internalSerialization;
	}

}
