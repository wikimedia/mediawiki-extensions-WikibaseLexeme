<?php

namespace Wikibase\Lexeme\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class Form implements StatementListProvider {

	/**
	 * @var FormId
	 */
	private $id;

	/**
	 * @var TermList
	 */
	private $representations;

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	/**
	 * @var StatementList
	 */
	private $statementList;

	/**
	 * @param FormId $id
	 * @param TermList $representations
	 * @param ItemId[] $grammaticalFeatures
	 * @param StatementList|null $statementList
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		FormId $id,
		TermList $representations,
		array $grammaticalFeatures,
		StatementList $statementList = null
	) {
		Assert::parameter(
			!$representations->isEmpty(),
			'$representations',
			'Form must have at least one representation'
		);
		Assert::parameterElementType( ItemId::class, $grammaticalFeatures, '$grammaticalFeatures' );

		$this->id = $id;
		$this->representations = $representations;
		$this->grammaticalFeatures = $grammaticalFeatures;
		$this->statementList = $statementList ?: new StatementList();
	}

	/**
	 * @return FormId
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return TermList
	 */
	public function getRepresentations() {
		return $this->representations;
	}

	/**
	 * @return ItemId[]
	 */
	public function getGrammaticalFeatures() {
		return $this->grammaticalFeatures;
	}

	/**
	 * @see StatementListProvider::getStatements()
	 */
	public function getStatements() {
		return $this->statementList;
	}

}
