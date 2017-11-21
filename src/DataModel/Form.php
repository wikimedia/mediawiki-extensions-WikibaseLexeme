<?php

namespace Wikibase\Lexeme\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikimedia\Assert\Assert;

/**
 * Mutable (e.g. the provided StatementList can be changed) implementation of a Lexeme's form in the
 * lexiographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Form
 *
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

		$this->id = $id;
		$this->representations = $representations;
		$this->setGrammaticalFeatures( $grammaticalFeatures );
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

	public function setGrammaticalFeatures( array $grammaticalFeatures ) {
		Assert::parameterElementType( ItemId::class, $grammaticalFeatures, '$grammaticalFeatures' );

		$result = [];
		foreach ( $grammaticalFeatures as $grammaticalFeature ) {
			if ( array_search( $grammaticalFeature, $result ) === false ) {
				$result[] = $grammaticalFeature;
			}
		}

		usort( $result, function ( ItemId $a, ItemId $b ) {
			return strcmp( $a->getSerialization(), $b->getSerialization() );
		} );

		$this->grammaticalFeatures = $result;
	}

	/**
	 * @see StatementListProvider::getStatements()
	 */
	public function getStatements() {
		return $this->statementList;
	}

}
