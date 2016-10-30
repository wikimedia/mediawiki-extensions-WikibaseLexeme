<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDeserializer extends TypedObjectDeserializer {

	/**
	 * @var Deserializer
	 */
	private $statementListDeserializer;

	/**
	 * @param Deserializer $statementListDeserializer
	 */
	public function __construct(
		Deserializer $statementListDeserializer
	) {
		parent::__construct( 'lexeme', 'type' );
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

}
