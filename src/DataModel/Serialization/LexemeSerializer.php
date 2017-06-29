<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\Sense;

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

		if ( $id !== null ) { // TODO: Should fail if ID is not present
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
		$serialization['senses'] = $this->serializeSenses( $lexeme->getSenses() );

		return $serialization;
	}

	/**
	 * @param Form[] $forms
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
	 * @param Form $form
	 *
	 * @return array
	 */
	private function serializeForm( Form $form ) {
		$serialization = [];

		$id = $form->getId();
		if ( $id !== null ) {
			// Note: This ID serialization is final, because there is no EntityIdSerializer
			$serialization['id'] = $id->getSerialization();
		}

		$serialization['representation'] = $form->getRepresentation();
		$serialization['grammaticalFeatures'] = array_map(
			function ( ItemId $itemId ) {
				return $itemId->getSerialization();
			},
			$form->getGrammaticalFeatures()
		);

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$form->getStatements()
		);

		return $serialization;
	}

	/**
	 * @param Sense[] $senses
	 *
	 * @return array[]
	 */
	private function serializeSenses( array $senses ) {
		$serialization = [];

		foreach ( $senses as $sense ) {
			$serialization[] = $this->serializeSense( $sense );
		}

		return $serialization;
	}

	/**
	 * @param Sense $sense
	 *
	 * @return array
	 */
	private function serializeSense( Sense $sense ) {
		$serialization = [];

		$serialization['id'] = $sense->getId()->getSerialization();
		$serialization['glosses'] = $this->termListSerializer->serialize( $sense->getGlosses() );

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$sense->getStatements()
		);

		return $serialization;
	}

}
