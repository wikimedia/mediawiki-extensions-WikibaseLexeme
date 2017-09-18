<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0+
 */
class AddFormRequestParser {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @param array $params
	 * @return AddFormRequestParserResult
	 */
	public function parse( array $params ) {
		$errors = $this->validateRequiredFieldsPresent( $params );
		if ( $errors ) {
			return AddFormRequestParserResult::newWithErrors( $errors );
		}

		$data = json_decode( $params['data'], true );
		if ( $data === null ) {
			return AddFormRequestParserResult::newWithErrors( [ 'data-invalid-json' ] );
		}

		$errors = $this->validateDataStructure( $data );
		if ( $errors ) {
			return AddFormRequestParserResult::newWithErrors( $errors );
		}

		$lexemeId = $this->parseLexemeId( $params['lexemeId'], $errors );
		$representations = $this->parseRepresentations( $data['representations'], $errors );
		$grammaticalFeatures = $this->parseGrammaticalFeatures( $data['grammaticalFeatures'], $errors );

		if ( $errors ) {
			return AddFormRequestParserResult::newWithErrors( $errors );
		}

		return AddFormRequestParserResult::newWithRequest(
			new AddFormRequest( $lexemeId, $representations, $grammaticalFeatures )
		);
	}

	private function validateDataStructure( $data ) {
		$errors = [];

		if ( !is_array( $data ) ) {
			return [ 'data-not-array' ];
		}

		if ( !array_key_exists( 'representations', $data ) ) {
			$errors[] = 'data-representations-key-missing';
		} elseif ( !is_array( $data['representations'] ) ) {
			$errors[] = 'data-representations-not-array';
		}

		if ( !array_key_exists( 'grammaticalFeatures', $data ) ) {
			$errors[] = 'data-grammatical-features-key-missing';
		} elseif ( !is_array( $data['grammaticalFeatures'] ) ) {
			$errors[] = 'data-grammatical-features-not-array';
		}

		return $errors;
	}

	/**
	 * @param string $id
	 * @return LexemeId|null
	 */
	private function parseLexemeId( $id, array &$errors ) {
		try {
			$lexemeId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			$errors[] = [ 'lexemeid-invalid', $id ];
			return null;
		}

		if ( $lexemeId->getEntityType() !== 'lexeme' ) {
			$errors[] = [ 'lexemeid-not-lexeme-id', $id ];
			return null;
		}

		return $lexemeId;
	}

	private function parseRepresentations( array $data, array &$errors ) {
		$representations = [];

		foreach ( $data as $index => $representationData ) {
			$incomplete = false;

			if ( !array_key_exists( 'representation', $representationData ) ) {
				$errors[] = [ 'representation-text-missing', $index ];
				$incomplete = true;
			}
			if ( !array_key_exists( 'language', $representationData ) ) {
				$errors[] = [ 'representation-language-missing', $index ];
				$incomplete = true;
			}

			if ( $incomplete ) {
				continue;
			}

			$representations[] = new Term(
				$representationData['language'],
				$representationData['representation']
			);
		}

		if ( empty( $representations ) ) {
			$errors[] = 'representations-empty';
		}

		return new TermList( $representations );
	}

	private function parseGrammaticalFeatures( $data, array &$errors ) {
		$features = [];

		foreach ( $data as $index => $featureId ) {
			try {
				$id = $this->entityIdParser->parse( $featureId );
			} catch ( EntityIdParsingException $e ) {
				$errors[] = [ 'grammatical-feature-itemid-invalid', $featureId ];
				continue;
			}

			if ( $id->getEntityType() !== 'item' ) {
				$errors[] = [ 'grammatical-feature-not-item-id', $featureId ];
				continue;
			}

			$features[] = $id;
		}

		return $features;
	}

	private function validateRequiredFieldsPresent( array $params ) {
		$errors = [];

		if ( !array_key_exists( 'lexemeId', $params ) ) {
			$errors[] = 'lexemeId-param-missing';
		}

		if ( !array_key_exists( 'data', $params ) ) {
			$errors[] = 'data-parame-missing';
		}

		return $errors;
	}

}
