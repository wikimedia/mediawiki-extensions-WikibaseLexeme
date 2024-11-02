<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

use LogicException;
use Wikibase\DataModel\Entity\ClearableEntity;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\DummyObjects\DummySenseId;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;
use Wikimedia\Assert\Assert;

/**
 * Mutable (e.g. the provided StatementList can be changed) implementation of a Lexeme's sense in
 * the lexicographical data model.
 *
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLexeme/Data_Model#Sense
 *
 * @license GPL-2.0-or-later
 */
class Sense implements StatementListProvidingEntity, ClearableEntity {

	public const ENTITY_TYPE = 'sense';

	protected SenseId $id;

	protected TermList $glossList;

	protected StatementList $statementList;

	public function __construct(
		SenseId $id,
		TermList $glossList,
		?StatementList $statementList = null
	) {
		$this->id = $id;
		$this->glossList = $glossList; // TODO: check there is at least gloss in one language provided
		$this->statementList = $statementList ?: new StatementList();
	}

	public function getId(): SenseId {
		return $this->id;
	}

	public function getGlosses(): TermList {
		return $this->glossList;
	}

	public function getStatements(): StatementList {
		return $this->statementList;
	}

	public function copy(): self {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 *
	 * @since 5.1
	 */
	public function __clone() {
		// TermList is mutable, but Term is not. No deeper cloning necessary.
		$this->glossList = clone $this->glossList;
		$this->statementList = clone $this->statementList;
	}

	public function getType(): string {
		return 'sense';
	}

	/**
	 * @param SenseId $id
	 */
	public function setId( $id ): void {
		Assert::parameterType( SenseId::class, $id, '$id' );

		if ( !( $this->id instanceof NullSenseId || $this->id instanceof DummySenseId ) ) {
			throw new LogicException( 'Cannot override a real SenseId' );
		}

		$this->id = $id;
	}

	public function isEmpty(): bool {
		return $this->glossList->isEmpty()
			&& $this->statementList->isEmpty();
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool True if the sense's contents are equal. Does not consider the ID.
	 */
	public function equals( $target ): bool {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $this->glossList->equals( $target->glossList )
			&& $this->statementList->equals( $target->statementList );
	}

	/**
	 * Clears the glosses and statements of a sense.
	 * Note that this leaves the sense in an insufficiently initialized state.
	 */
	public function clear(): void {
		$this->glossList = new TermList();
		$this->statementList = new StatementList();
	}

}
