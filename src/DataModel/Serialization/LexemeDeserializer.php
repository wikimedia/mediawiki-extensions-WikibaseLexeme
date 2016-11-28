<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDeserializer extends TypedObjectDeserializer {

	/**
	 * @var TermDeserializer
	 */
	private $termDeserializer;

	/**
	 * @var StatementListDeserializer
	 */
	private $statementListDeserializer;

	/**
	 * @param TermDeserializer $termDeserializer
	 * @param StatementListDeserializer $statementListDeserializer
	 */
	public function __construct(
		TermDeserializer $termDeserializer,
		StatementListDeserializer $statementListDeserializer
	) {
		parent::__construct( 'lexeme', 'type' );
		$this->termDeserializer = $termDeserializer;
		$this->statementListDeserializer = $statementListDeserializer;
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
			$this->deserializeLemma( $serialization ),
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
	 * @return Term|null
	 */
	private function deserializeLemma( array $serialization ) {
		if ( array_key_exists( 'lemma', $serialization ) ) {
			return $this->termDeserializer->deserialize( $serialization['lemma'] );
		}

		return null;
	}

}
