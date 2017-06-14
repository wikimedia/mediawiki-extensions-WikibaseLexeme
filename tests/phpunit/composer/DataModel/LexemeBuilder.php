<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Sense;

class LexemeBuilder {

	/**
	 * @var ItemId
	 */
	private $lexicalCategory;

	/**
	 * @var ItemId
	 */
	private $language;

	/**
	 * @var LexemeId|null
	 */
	private $lexemeId;

	/**
	 * @var Snak[]
	 */
	private $statements = [];

	/**
	 * @var string[] Lemmas indexed by language
	 */
	private $lemmas = [];

	/**
	 * @var Sense[]
	 */
	private $senses = [];

	public function __construct() {
		$this->lexicalCategory = $this->newRandomItemId();
		$this->language = $this->newRandomItemId();
	}

	public static function create() {
		return new self();
	}

	public function build() {
		$lexeme = new Lexeme(
			$this->lexemeId,
			null,
			$this->lexicalCategory,
			$this->language,
			null,
			[],
			$this->senses
		);

		$lemmas = new TermList();
		foreach ( $this->lemmas as $lang => $term ) {
			$lemmas->setTextForLanguage( $lang, $term );
		}
		$lexeme->setLemmas( $lemmas );

		foreach ( $this->statements as $statement ) {
			$lexeme->getStatements()->addNewStatement( $statement );
		}

		return $lexeme;
	}

	/**
	 * @param ItemId|string $itemId
	 * @return LexemeBuilder
	 */
	public function withLexicalCategory( $itemId ) {
		$result = clone $this;
		if ( !$itemId instanceof ItemId ) {
			$itemId = new ItemId( $itemId );
		}
		$result->lexicalCategory = $itemId;
		return $result;
	}

	/**
	 * @param ItemId|string $itemId
	 * @return LexemeBuilder
	 */
	public function withLanguage( $itemId ) {
		$result = clone $this;
		if ( !$itemId instanceof ItemId ) {
			$itemId = new ItemId( $itemId );
		}
		$result->language = $itemId;
		return $result;
	}

	/**
	 * @param LexemeId|string $lexemeId
	 * @return LexemeBuilder
	 */
	public function withId( $lexemeId ) {
		$result = clone $this;
		if ( !$lexemeId instanceof LexemeId ) {
			$lexemeId = new LexemeId( $lexemeId );
		}
		$result->lexemeId = $lexemeId;
		return $result;
	}

	public function withStatement( Snak $statement ) {
		$result = clone $this;
		$result->statements[] = clone $statement;
		return $result;
	}

	/**
	 * @param string $language
	 * @param string $lemma
	 * @return LexemeBuilder
	 */
	public function withLemma( $language, $lemma ) {
		$result = clone $this;
		$result->lemmas[$language] = $lemma;
		return $result;
	}

	private function newRandomItemId() {
		return new ItemId( 'Q' . mt_rand( 1, mt_getrandmax() ) );
	}

	public function __clone() {
		$statements = [];
		foreach ( $this->statements as $statement ) {
			$statements[] = clone $statement;
		}
		$this->statements = $statements;
	}

	/**
	 * @param Sense|SenseBuilder $sense
	 * @return self
	 */
	public function withSense( $sense ) {
		$result = clone $this;

		if ( $sense instanceof SenseBuilder ) {
			$sense = $sense->build();
		} elseif ( !$sense instanceof Sense ) {
			throw new \InvalidArgumentException( '$sense has incorrect type' );
		}

		$result->senses[] = $sense;
		return $result;
	}

}
