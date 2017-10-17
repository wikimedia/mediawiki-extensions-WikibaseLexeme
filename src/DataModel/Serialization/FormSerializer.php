<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\Lexeme\DataModel\Form;

/**
 * @license GPL-2.0+
 */
class FormSerializer {

	/**
	 * @var TermListSerializer
	 */
	private $termListSerializer;

	/**
	 * @var StatementListSerializer
	 */
	private $statementListSerializer;

	public function __construct(
		TermListSerializer $termListSerializer,
		StatementListSerializer $statementListSerializer
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
