<?php

namespace Wikibase\Lexeme\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class Lexeme implements EntityDocument, StatementListProvider {

	const ENTITY_TYPE = 'lexeme';

	/**
	 * @var LexemeId|null
	 */
	private $id;

	/**
	 * @var StatementList
	 */
	private $statements;

	/**
	 * @param LexemeId|null $id
	 * @param StatementList|null $statements
	 */
	public function __construct(
		LexemeId $id = null,
		StatementList $statements = null
	) {
		// TODO: add lemma, language and lexical category
		$this->id = $id;
		$this->statements = $statements ?: new StatementList();
	}

	/**
	 * @return EntityId|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return self::ENTITY_TYPE;
	}

	/**
	 * @return StatementList
	 */
	public function getStatements() {
		return $this->statements;
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws InvalidArgumentException
	 */
	public function setId( $id ) {
		Assert::parameterType( LexemeId::class, $id, '$id' );

		$this->id = $id;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		// TODO: should also check other attributes once implemented
		return $this->statements->isEmpty();
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		// TODO: should also check other attributes once implemented
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $this->statements->equals( $target->statements );
	}

	/**
	 * @see EntityDocument::copy
	 *
	 * @return Lexeme
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 */
	public function __clone() {
		// TODO: should also clone other attributes once implemented
		$this->statements = unserialize( serialize( $this->statements ) );
	}
}
