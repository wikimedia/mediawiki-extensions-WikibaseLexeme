<?php

namespace Wikibase\Lexeme\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\Lexeme\Domain\Model\Sense;

/**
 * @license GPL-2.0-or-later
 */
class SenseSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $termListSerializer;

	/**
	 * @var Serializer
	 */
	private $statementListSerializer;

	public function __construct(
		Serializer $termListSerializer,
		Serializer $statementListSerializer
	) {
		$this->termListSerializer = $termListSerializer;
		$this->statementListSerializer = $statementListSerializer;
	}

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Sense;
	}

	/**
	 * @param Sense $sense
	 *
	 * @return array
	 */
	public function serialize( $sense ) {
		if ( !( $sense instanceof Sense ) ) {
			throw new UnsupportedObjectException(
				$sense,
				'SenseSerializer can only serialize Sense objects.'
			);
		}

		$serialization = [];

		$serialization['id'] = $sense->getId()->getSerialization();

		$serialization['glosses'] = $this->termListSerializer->serialize( $sense->getGlosses() );

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$sense->getStatements()
		);

		return $serialization;
	}

}
