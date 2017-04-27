<?php

namespace Wikibase\Lexeme\DataModel;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeForm {

	/**
	 * @var LexemeFormId|null
	 */
	private $id;

	/**
	 * @var string
	 */
	private $representation;

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	/**
	 * @param LexemeFormId $id |null
	 * @param string $representation
	 * @param ItemId[] $grammaticalFeatures
	 */
	public function __construct(
		LexemeFormId $id = null,
		$representation,
		array $grammaticalFeatures
	) {
		$this->id = $id;
		$this->representation = $representation;
		$this->grammaticalFeatures = $grammaticalFeatures;
	}

	/**
	 * @return LexemeFormId|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getRepresentation() {
		return $this->representation;
	}

	public function getGrammaticalFeatures() {
		return $this->grammaticalFeatures;
	}

}
