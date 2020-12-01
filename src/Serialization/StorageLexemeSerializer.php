<?php

namespace Wikibase\Lexeme\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use UnexpectedValueException;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\SenseSet;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class StorageLexemeSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $termListSerializer;

	/**
	 * @var Serializer
	 */
	private $statementListSerializer;

	/**
	 * @var Serializer
	 */
	private $formSerializer;

	/**
	 * @var Serializer
	 */
	private $senseSerializer;

	public function __construct(
		Serializer $termListSerializer,
		Serializer $statementListSerializer
	) {
		$this->termListSerializer = $termListSerializer;
		$this->statementListSerializer = $statementListSerializer;
		$this->formSerializer = new FormSerializer( $termListSerializer, $statementListSerializer );
		$this->senseSerializer = new SenseSerializer(
			$termListSerializer,
			$statementListSerializer
		);
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

		if ( $id !== null ) { // FIXME: Should fail if ID is not present
			$serialization['id'] = $id->getSerialization();
		}

		// FIXME: Should always present
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

		$serialization['nextFormId'] = $lexeme->getNextFormId();
		$serialization['nextSenseId'] = $lexeme->getNextSenseId();

		$serialization['forms'] = $this->serializeForms( $lexeme->getForms() );
		$serialization['senses'] = $this->serializeSenses( $lexeme->getSenses() );

		return $serialization;
	}

	/**
	 * @param FormSet $forms
	 *
	 * @return array[]
	 */
	private function serializeForms( FormSet $forms ) {
		$serialization = [];

		foreach ( $forms->toArray() as $form ) {
			$serialization[] = $this->formSerializer->serialize( $form );
		}

		return $serialization;
	}

	/**
	 * @param SenseSet $senses
	 *
	 * @return array[]
	 */
	private function serializeSenses( SenseSet $senses ) {
		$serialization = [];

		foreach ( $senses->toArray() as $sense ) {
			$serialization[] = $this->senseSerializer->serialize( $sense );
		}

		return $serialization;
	}

}
