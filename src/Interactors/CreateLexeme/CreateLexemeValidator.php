<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use InvalidArgumentException;
use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\ItemExistenceChecker;
use Wikibase\Lexeme\Validation\LemmaLanguageCodeValidator;
use Wikibase\Lexeme\Validation\TagsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeValidator {

	private ?LexemeWriteModel $lexeme = null;
	private ?EditMetadata $editMetadata = null;

	public function __construct(
		private LemmaLanguageCodeValidator $lemmaLanguageCodeValidator,
		private ItemExistenceChecker $itemExistenceChecker,
		private int $maxLemmaLength,
		private TagsRetriever $tagsRetriever,
		private int $maxCommentLength,
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
		$lexicalCategory = $this->validateAndDeserializeItemId(
			$serialization['lexical_category'],
			'/lexeme/lexical_category'
		);
		if ( !array_key_exists( 'language', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'language' );
		}
		$language = $this->validateAndDeserializeItemId( $serialization['language'], '/lexeme/language' );

		$this->validateEditTags( $request->editTags );
		$this->validateComment( $request->comment );

		$this->lexeme = new LexemeWriteModel( null, $lemmas, $lexicalCategory, $language );
		$this->editMetadata = new EditMetadata(
			$request->editTags,
			$request->isBot,
			$request->comment,
			EditSummaryAction::CREATE_LEXEME,
		);
	}

	public function getValidatedLexeme(): LexemeWriteModel {
		if ( $this->lexeme === null ) {
			throw new LogicException( 'Must not call getValidatedLexeme() before validateAndDeserialize()' );
		}

		return $this->lexeme;
	}

	public function getValidatedEditMetadata(): EditMetadata {
		if ( $this->editMetadata === null ) {
			throw new LogicException( 'Must not call getValidatedEditMetadata() before validateAndDeserialize()' );
		}

		return $this->editMetadata;
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateEditTags( array $editTags ): void {
		$allowedTags = $this->tagsRetriever->getAllowedTags();
		foreach ( array_values( $editTags ) as $index => $tag ) {
			if ( !in_array( $tag, $allowedTags ) ) {
				throw UseCaseError::newInvalidValue( "/tags/$index" );
			}
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateComment( ?string $comment ): void {
		if ( $comment !== null && strlen( $comment ) > $this->maxCommentLength ) {
			throw UseCaseError::newValueTooLong( '/comment', $this->maxCommentLength );
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeItemId( mixed $value, string $path ): ItemId {
		if ( !is_string( $value ) ) {
			throw UseCaseError::newInvalidValue( $path );
		}
		try {
			$itemId = new ItemId( $value );
		} catch ( InvalidArgumentException ) {
			throw UseCaseError::newInvalidValue( $path );
		}
		if ( !$this->itemExistenceChecker->exists( $itemId ) ) {
			throw UseCaseError::newReferencedResourceNotFound( $path );
		}

		return $itemId;
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
