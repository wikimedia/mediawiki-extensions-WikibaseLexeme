<?php

namespace Wikibase\Lexeme\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Providers\LanguageProvider;
use Wikibase\Lexeme\DataModel\Providers\LemmasProvider;
use Wikibase\Lexeme\DataModel\Providers\LexicalCategoryProvider;

/**
 * @license GPL-2.0+
 */
class Lexeme implements EntityDocument, StatementListProvider, FingerprintProvider,
		LabelsProvider, DescriptionsProvider, LemmasProvider, LexicalCategoryProvider,
		LanguageProvider {

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
	 * @var TermList|null
	 */
	private $lemmas;

	/**
	 * @var ItemId|null
	 */
	private $lexicalCategory;

	/**
	 * @var ItemId|null
	 */
	private $language;

	/**
	 * @param LexemeId|null $id
	 * @param TermList|null $lemmas
	 * @param ItemId|null $lexicalCategory
	 * @param ItemId|null $language
	 * @param StatementList|null $statements
	 */
	public function __construct(
		LexemeId $id = null,
		TermList $lemmas = null,
		ItemId $lexicalCategory = null,
		ItemId $language = null,
		StatementList $statements = null
	) {
		$this->id = $id;
		$this->lemmas = $lemmas;
		$this->lexicalCategory = $lexicalCategory;
		$this->language = $language;
		$this->statements = $statements ?: new StatementList();
		// TODO: Remove this once Wikibase can work without fingerprint
		$this->fingerprint = new Fingerprint();
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
		return ( $this->lemmas === null
			|| $this->lemmas->isEmpty() )
			&& $this->lexicalCategory === null
			&& $this->language === null
			&& $this->statements->isEmpty();
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		if ( !( $target instanceof self ) ) {
			return false;
		}

		$sameLemmas = ( $this->lemmas === $target->getLemmas() || (
			$this->lemmas !== null
			&& $this->lemmas->equals( $target->getLemmas() ) )
		);

		$sameLexicalCategory = ( $this->lexicalCategory === $target->getLexicalCategory() || (
				$this->lexicalCategory !== null
				&& $this->lexicalCategory->equals( $target->getLexicalCategory() ) )
		);

		$sameLanguage = ( $this->language === $target->getLanguage() || (
				$this->language !== null
				&& $this->language->equals( $target->getLanguage() ) )
		);

		return $sameLemmas
			&& $sameLexicalCategory
			&& $sameLanguage
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
	 * @return TermList|null
	 */
	public function getLemmas() {
		return $this->lemmas;
	}

	/**
	 * @param TermList $lemmas
	 */
	public function setLemmas( TermList $lemmas ) {
		$this->lemmas = $lemmas;
	}

	/**
	 * @return ItemId|null
	 */
	public function getLexicalCategory() {
		return $this->lexicalCategory;
	}

	/**
	 * @param ItemId|null $lexicalCategory
	 */
	public function setLexicalCategory( ItemId $lexicalCategory = null ) {
		$this->lexicalCategory = $lexicalCategory;
	}

	/**
	 * @return ItemId|null
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * @param ItemId|null $language
	 */
	public function setLanguage( ItemId $language = null ) {
		$this->language = $language;
	}

}
