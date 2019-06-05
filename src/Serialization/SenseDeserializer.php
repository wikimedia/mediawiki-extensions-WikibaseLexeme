<?php

namespace Wikibase\Lexeme\Serialization;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class SenseDeserializer implements Deserializer {

	/**
	 * @var TermListDeserializer
	 */
	private $termListDeserializer;

	/**
	 * @var StatementListDeserializer
	 */
	private $statementListDeserializer;

	public function __construct(
		TermListDeserializer $termListDeserializer,
		StatementListDeserializer $statementListDeserializer
	) {
		$this->termListDeserializer = $termListDeserializer;
		$this->statementListDeserializer = $statementListDeserializer;
	}

	/**
	 * @param array $serialization
	 * @return Sense
	 */
	public function deserialize( $serialization ) {
		return new Sense(
			$this->deserializeId( $serialization ),
			$this->deserializeGlossList( $serialization ),
			$this->deserializeStatementList( $serialization )
		);
	}

	/**
	 * @param array $serialization
	 * @return SenseId
	 */
	private function deserializeId( array $serialization ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			throw new DeserializationException( 'Sense is missing id' );
		}

		return new SenseId( $serialization['id'] );
	}

	/**
	 * @param array $serialization
	 * @return TermList
	 */
	private function deserializeGlossList( array $serialization ) {
		if ( !array_key_exists( 'glosses', $serialization ) ) {
			throw new DeserializationException( 'Sense is missing glosses' );
		}

		return $this->termListDeserializer->deserialize( $serialization['glosses'] );
	}

	/**
	 * @param array $serialization
	 * @return StatementList
	 */
	private function deserializeStatementList( array $serialization ) {
		if ( !array_key_exists( 'claims', $serialization ) ) {
			throw new DeserializationException( 'Sense is missing claims' );
		}

		return $this->statementListDeserializer->deserialize( $serialization['claims'] );
	}

}
