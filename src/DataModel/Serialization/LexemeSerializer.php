<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\Lexeme\DataModel\Lexeme;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeSerializer implements DispatchableSerializer {

	/**
	 * @var TermSerializer
	 */
	private $termSerializer;

	/**
	 * @var StatementListSerializer
	 */
	private $statementListSerializer;

	/**
	 * @param TermSerializer $termSerializer
	 * @param StatementListSerializer $statementListSerializer
	 */
	public function __construct(
		TermSerializer $termSerializer,
		StatementListSerializer $statementListSerializer
	) {
		$this->termSerializer = $termSerializer;
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
				'LexemeSerializer can only serialize Lexeme objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	/**
	 * @param Lexeme $lexeme
	 * @return array
	 */
	private function getSerialized( Lexeme $lexeme ) {
		$serialization = [ 'type' => $lexeme->getType() ];

		$id = $lexeme->getId();

		if ( $id !== null ) {
			$serialization['id'] = $id->getSerialization();
		}

		if ( $lexeme->getLemma() !== null ) {
			$serialization['lemma'] = $this->termSerializer->serialize(
				$lexeme->getLemma()
			);
		}

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$lexeme->getStatements()
		);

		return $serialization;
	}

}
