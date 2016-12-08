<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDeserializer extends TypedObjectDeserializer {

	/**
	 * @var EntityIdDeserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @var TermListDeserializer
	 */
	private $termListDeserializer;

	/**
	 * @var StatementListDeserializer
	 */
	private $statementListDeserializer;

	/**
	 * @param TermListDeserializer $termListDeserializer
	 * @param StatementListDeserializer $statementListDeserializer
	 */
	public function __construct(
		EntityIdDeserializer $entityIdDeserializer,
		TermListDeserializer $termListDeserializer,
		StatementListDeserializer $statementListDeserializer
	) {
		parent::__construct( 'lexeme', 'type' );
		$this->termListDeserializer = $termListDeserializer;
		$this->statementListDeserializer = $statementListDeserializer;
		$this->entityIdDeserializer = $entityIdDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @throws DeserializationException
	 * @return Lexeme
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return new Lexeme(
			$this->deserializeId( $serialization ),
			$this->deserializeLemmas( $serialization ),
			$this->deserializeLexicalCategory( $serialization ),
			$this->deserializeStatements( $serialization )
		);
	}

	/**
	 * @param array $serialization
	 *
	 * @return LexemeId|null
	 */
	private function deserializeId( array $serialization ) {
		if ( array_key_exists( 'id', $serialization ) ) {
			return new LexemeId( $serialization['id'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return StatementList|null
	 */
	private function deserializeStatements( array $serialization ) {
		if ( array_key_exists( 'claims', $serialization ) ) {
			return $this->statementListDeserializer->deserialize( $serialization['claims'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return TermList|null
	 */
	private function deserializeLemmas( array $serialization ) {
		if ( array_key_exists( 'lemmas', $serialization ) ) {
			return $this->termListDeserializer->deserialize( $serialization['lemmas'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return ItemId|null
	 */
	private function deserializeLexicalCategory( array $serialization ) {
		if ( array_key_exists( 'lexicalCategory', $serialization ) ) {
			return $this->entityIdDeserializer->deserialize( $serialization['lexicalCategory'] );
		}

		return null;
	}

}
