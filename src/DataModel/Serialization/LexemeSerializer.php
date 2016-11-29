<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\Lexeme\DataModel\Lexeme;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeSerializer implements DispatchableSerializer {

	/**
	 * @var TermListSerializer
	 */
	private $termListSerializer;

	/**
	 * @var StatementListSerializer
	 */
	private $statementListSerializer;

	/**
	 * @param TermListSerializer $termListSerializer
	 * @param StatementListSerializer $statementListSerializer
	 */
	public function __construct(
		TermListSerializer $termListSerializer,
		StatementListSerializer $statementListSerializer
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

		if ( $lexeme->getLemmata() !== null ) {
			$serialization['lemmata'] = $this->termListSerializer->serialize(
				$lexeme->getLemmata()
			);
		}

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$lexeme->getStatements()
		);

		return $serialization;
	}

}
