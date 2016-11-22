<?php

namespace Wikibase\Lexeme\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Providers\LemmaProvider;

/**
 * @license GPL-2.0+
 */
class Lexeme implements EntityDocument, StatementListProvider, FingerprintProvider,
		LabelsProvider, DescriptionsProvider, LemmaProvider {

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
	 * @var Fingerprint
	 */
	private $fingerprint;

	/**
	 * @var Term
	 */
	private $lemma;

	/**
	 * @param LexemeId|null $id
	 * @param Term|null $lemma
	 * @param StatementList|null $statements
	 */
	public function __construct(
		LexemeId $id = null,
		Term $lemma = null,
		StatementList $statements = null
	) {
		// TODO: add lemma, language and lexical category
		$this->id = $id;
		$this->statements = $statements ?: new StatementList();
		// TODO: Remove this once Wikibase can work without fingerprint
		$this->fingerprint = new Fingerprint();
		$this->lemma = $lemma;
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
	 * @return Fingerprint
	 */
	public function getFingerprint() {
		return $this->fingerprint;
	}

	/**
	 * @param Fingerprint $fingerprint
	 */
	public function setFingerprint( Fingerprint $fingerprint ) {
		$this->fingerprint = $fingerprint;
	}

	/**
	 * @param LexemeId|int $id
	 *
	 * @throws InvalidArgumentException
	 */
	public function setId( $id ) {
		if ( $id instanceof LexemeId ) {
			$this->id = $id;
		} elseif ( is_int( $id ) ) {
			$this->id = new LexemeId( 'L' . $id );
		} else {
			throw new InvalidArgumentException(
				'$id must be an instance of LexemeId or an integer.'
			);
		}
	}

	/**
	 * Workaround for T150084
	 *
	 * @return TermList
	 */
	public function getDescriptions() {
		return new TermList();
	}

	/**
	 * Workaround for T150084
	 *
	 * @return TermList
	 */
	public function getLabels() {
		return new TermList();
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

	/**
	 * @return Term
	 */
	public function getLemma() {
		return $this->lemma;
	}

	/**
	 * @param Term $lemma
	 */
	public function setLemma( Term $lemma ) {
		$this->lemma = $lemma;
	}

}
