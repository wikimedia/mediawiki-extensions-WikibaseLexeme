<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use UnexpectedValueException;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeForm;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeSerializer implements DispatchableSerializer {

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
	 *
	 * @throws SerializationException
	 * @return array
	 */
	private function getSerialized( Lexeme $lexeme ) {
		$serialization = [ 'type' => $lexeme->getType() ];

		$id = $lexeme->getId();

		if ( $id !== null ) {
			$serialization['id'] = $id->getSerialization();
		}

		if ( !$lexeme->getLemmas()->isEmpty() ) {
			$serialization['lemmas'] = $this->termListSerializer->serialize(
				$lexeme->getLemmas()
			);
		}

		try {
			$serialization['lexicalCategory'] = $lexeme->getLexicalCategory()->getSerialization();
			$serialization['language'] = $lexeme->getLanguage()->getSerialization();
		} catch ( UnexpectedValueException $ex ) {
			throw new UnsupportedObjectException(
				$lexeme,
				'Can not serialize incomplete Lexeme',
				$ex
			);
		}

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$lexeme->getStatements()
		);

		$serialization['forms'] = $this->serializeForms( $lexeme->getForms() );

		return $serialization;
	}

	/**
	 * @param LexemeForm[] $forms
	 *
	 * @return array[]
	 */
	private function serializeForms( array $forms ) {
		$serialization = [];

		foreach ( $forms as $form ) {
			$serialization[] = $this->serializeForm( $form );
		}

		return $serialization;
	}

	/**
	 * @param LexemeForm $form
	 *
	 * @return array
	 */
	private function serializeForm( LexemeForm $form ) {
		$serialization = [];

		$id = $form->getId();
		if ( $id !== null ) {
			// Note: This ID serialization is final, because there is no EntityIdSerializer
			$serialization['id'] = $id->getSerialization();
		}

		$serialization['representation'] = $form->getRepresentation();
		return $serialization;
	}

}
