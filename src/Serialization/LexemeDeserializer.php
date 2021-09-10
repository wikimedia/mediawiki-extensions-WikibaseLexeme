<?php

namespace Wikibase\Lexeme\Serialization;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseSet;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDeserializer extends TypedObjectDeserializer {

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @var Deserializer
	 */
	private $termListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $statementListDeserializer;

	/**
	 * @var SenseDeserializer
	 */
	private $senseDeserializer;

	public function __construct(
		Deserializer $entityIdDeserializer,
		Deserializer $statementListDeserializer
	) {
		parent::__construct( 'lexeme', 'type' );

		$this->entityIdDeserializer = $entityIdDeserializer;
		$this->termListDeserializer = new TermListDeserializer( new TermDeserializer() );
		$this->statementListDeserializer = $statementListDeserializer;
		$this->senseDeserializer = new SenseDeserializer(
			$this->termListDeserializer,
			// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType
			$statementListDeserializer
		);
	}

	/**
	 * @param array $serialization
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
			$this->deserializeLanguage( $serialization ),
			$this->deserializeStatements( $serialization ),
			$serialization['nextFormId'],
			$this->deserializeForms( $serialization ),
			$this->deserializeNextSenseId( $serialization ),
			$this->deserializeSenses( $serialization )
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

	/**
	 * @param array $serialization
	 *
	 * @return ItemId|null
	 */
	private function deserializeLanguage( array $serialization ) {
		if ( array_key_exists( 'language', $serialization ) ) {
			return $this->entityIdDeserializer->deserialize( $serialization['language'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return FormSet
	 */
	private function deserializeForms( array $serialization ) {
		// TODO: Extract to a FormsDeserializer
		$forms = new FormSet( [] );

		if ( array_key_exists( 'forms', $serialization ) ) {
			foreach ( $serialization['forms'] as $formSerialization ) {
				$forms->add( $this->deserializeForm( $formSerialization ) );
			}
		}

		return $forms;
	}

	/**
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return Form
	 */
	private function deserializeForm( array $serialization ) {
		$id = null;

		if ( array_key_exists( 'id', $serialization ) ) {
			// We may want to use an EntityIdDeserializer here
			$id = new FormId( $serialization['id'] );
		} else {
			throw new DeserializationException( "No id found in Form serialization" );
		}

		$representations = $this->termListDeserializer->deserialize(
			$serialization['representations']
		);

		$grammaticalFeatures = [];
		foreach ( $serialization['grammaticalFeatures'] as $featureId ) {
			$grammaticalFeatures[] = $this->entityIdDeserializer->deserialize( $featureId );
		}

		$statements = $this->statementListDeserializer->deserialize( $serialization['claims'] );

		return new Form( $id, $representations, $grammaticalFeatures, $statements );
	}

	private function deserializeSenses( array $serialization ) {
		$senses = new SenseSet();

		if ( array_key_exists( 'senses', $serialization ) ) {
			foreach ( $serialization['senses'] as $senseSerialization ) {
				$senses->add( $this->senseDeserializer->deserialize( $senseSerialization ) );
			}
		}

		return $senses;
	}

	/**
	 * @param array $serialization
	 * @return int
	 */
	private function deserializeNextSenseId( array $serialization ) {
		if ( array_key_exists( 'nextSenseId', $serialization ) ) {
			return $serialization['nextSenseId'];
		} else {
			if ( array_key_exists( 'senses', $serialization ) && $serialization['senses'] !== [] ) {
				throw new DeserializationException(
					'Lexeme serialization has senses but no nextSenseId'
				);
			}
			return 1;
		}
	}

}
