<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Form;

/**
 * @license GPL-2.0+
 */
class FormSerializer {

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
	 * @param Form $form
	 *
	 * @return array
	 */
	public function serialize( Form $form ) {
		$serialization = [];

		$serialization['id'] = $form->getId()->getSerialization();

		$serialization['representations'] = $this->termListSerializer->serialize(
			$form->getRepresentations()
		);
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

}
