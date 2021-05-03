<?php

namespace Wikibase\Lexeme\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Form;

/**
 * @license GPL-2.0-or-later
 */
class FormSerializer implements DispatchableSerializer {

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
		return $object instanceof Form;
	}

	/**
	 * @param Form $form
	 *
	 * @return array
	 */
	public function serialize( $form ) {
		if ( !( $form instanceof Form ) ) {
			throw new UnsupportedObjectException(
				$form,
				'FormSerializer can only serialize Form objects.'
			);
		}

		$serialization = [];

		$serialization['id'] = $form->getId()->getSerialization();

		$serialization['representations'] = $this->termListSerializer->serialize(
			$form->getRepresentations()
		);
		$serialization['grammaticalFeatures'] = array_map(
			static function ( ItemId $itemId ) {
				return $itemId->getSerialization();
			},
			$form->getGrammaticalFeatures()
		);

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$form->getStatements()
		);

		return $serialization;
	}

}
