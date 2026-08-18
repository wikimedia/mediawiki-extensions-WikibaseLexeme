<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\LemmaLanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeValidator {

	private ?LexemeWriteModel $lexeme = null;

	public function __construct(
		private LemmaLanguageCodeValidator $lemmaLanguageCodeValidator,
		private int $maxLemmaLength,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( CreateLexemeRequest $request ): void {
		$serialization = $request->lexeme;

		if ( !array_key_exists( 'lemmas', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'lemmas' );
		}
		$lemmas = $this->validateAndDeserializeLemmas( $serialization['lemmas'] );
		if ( !array_key_exists( 'lexical_category', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'lexical_category' );
		}
		if ( !array_key_exists( 'language', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'language' );
		}

		$this->lexeme = new LexemeWriteModel(
			null,
			$lemmas,
			new ItemId( $serialization['lexical_category'] ),
			new ItemId( $serialization['language'] ),
		);
	}

	public function getValidatedLexeme(): LexemeWriteModel {
		if ( $this->lexeme === null ) {
			throw new LogicException( 'Must not call getValidatedLexeme() before validateAndDeserialize()' );
		}

		return $this->lexeme;
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeLemmas( mixed $lemmas ): TermList {
		if ( !is_array( $lemmas ) || !$lemmas || array_is_list( $lemmas ) ) {
			throw UseCaseError::newInvalidValue( '/lexeme/lemmas' );
		}

		$terms = [];
		foreach ( $lemmas as $languageCode => $text ) {
			$languageCode = (string)$languageCode;
			if ( !$this->lemmaLanguageCodeValidator->isValid( $languageCode ) ) {
				throw UseCaseError::newInvalidKey( '/lexeme/lemmas', $languageCode );
			}
			$terms[] = new Term( $languageCode, $this->validateLemmaText( $text, $languageCode ) );
		}

		return new TermList( $terms );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateLemmaText( mixed $text, string $languageCode ): string {
		if ( !is_string( $text ) ) {
			throw UseCaseError::newInvalidValue( "/lexeme/lemmas/$languageCode" );
		}
		$text = trim( $text );
		if ( $text === '' || preg_match( '/[\v\t]/u', $text ) ) {
			throw UseCaseError::newInvalidValue( "/lexeme/lemmas/$languageCode" );
		}
		if ( mb_strlen( $text ) > $this->maxLemmaLength ) {
			throw UseCaseError::newValueTooLong( "/lexeme/lemmas/$languageCode", $this->maxLemmaLength );
		}

		return $text;
	}

}
