<?php

namespace Wikibase\Lexeme\Domain\Model;

use InvalidArgumentException;
use LogicException;
use Wikibase\DataModel\Entity\ClearableEntity;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Domain\DummyObjects\NullFormId;
use Wikimedia\Assert\Assert;

/**
 * Mutable (e.g. the provided StatementList can be changed) implementation of a Lexeme's form in the
 * lexicographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Form
 *
 * @license GPL-2.0-or-later
 */
class Form implements StatementListProvidingEntity, ClearableEntity {

	public const ENTITY_TYPE = 'form';

	/**
	 * @var FormId
	 */
	protected $id;

	/**
	 * @var TermList
	 */
	protected $representations;

	/**
	 * @var ItemId[]
	 */
	protected $grammaticalFeatures;

	/**
	 * @var StatementList
	 */
	protected $statementList;

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
		$this->id = $id;
		$this->representations = $representations;
		$this->setGrammaticalFeatures( $grammaticalFeatures );
		$this->statementList = $statementList ?: new StatementList();
	}

	/**
	 * @return string
	 */
	public function getType() {
		return self::ENTITY_TYPE;
	}

	/**
	 * @return FormId
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param FormId $id
	 */
	public function setId( $id ) {
		Assert::parameterType( FormId::class, $id, '$id' );

		// all dummy FormIds are also FormIds that's why this check looks overly complicated
		if ( !( $this->id instanceof NullFormId || $this->id instanceof DummyFormId ) ) {
			throw new LogicException( 'Cannot override a real FormId' );
		}

		$this->id = $id;
	}

	/**
	 * The representations of the Form as list of terms.
	 *
	 * Note that in some places "representation" means just the text of a representation and the
	 * language code is called "spelling variant".
	 *
	 * @return TermList
	 */
	public function getRepresentations() {
		return $this->representations;
	}

	public function setRepresentations( TermList $representations ) {
		$this->representations = $representations;
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
			if ( !in_array( $grammaticalFeature, $result ) ) {
				$result[] = $grammaticalFeature;
			}
		}

		usort( $result, static function ( ItemId $a, ItemId $b ) {
			return strcmp( $a->getSerialization(), $b->getSerialization() );
		} );

		$this->grammaticalFeatures = $result;
	}

	/** @inheritDoc */
	public function getStatements() {
		return $this->statementList;
	}

	/**
	 * @see EntityDocument::isEmpty
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->representations->isEmpty()
			&& $this->grammaticalFeatures === []
			&& $this->statementList->isEmpty();
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool True if the forms contents are equal. Does not consider the ID.
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $this->representations->equals( $target->representations )
			&& $this->grammaticalFeatures == $target->grammaticalFeatures
			&& $this->statementList->equals( $target->statementList );
	}

	/**
	 * @see EntityDocument::copy
	 *
	 * @return self
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * The forms ID and grammatical features (a set of ItemIds) are immutable and don't need
	 * individual cloning.
	 */
	public function __clone() {
		$this->representations = clone $this->representations;
		$this->statementList = clone $this->statementList;
	}

	/**
	 * Clears the representations, grammatical features and statements of a form.
	 * Note that this leaves the form in an insufficiently initialized state.
	 */
	public function clear() {
		$this->representations = new TermList();
		$this->grammaticalFeatures = [];
		$this->statementList = new StatementList();
	}

}
