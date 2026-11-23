<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Validation;

use LogicException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * Validates and deserializes Lexeme terms, i.e. lemmas, Form representations and Sense glosses.
 *
 * @license GPL-2.0-or-later
 */
class LexemeTermsValidator {

	public const string CODE_INVALID_KEY = 'lexeme-terms-validator-code-invalid-key';
	public const string CODE_INVALID_VALUE = 'lexeme-terms-validator-code-invalid-value';
	public const string CODE_VALUE_TOO_LONG = 'lexeme-terms-validator-code-value-too-long';

	public const string CONTEXT_PATH = 'lexeme-terms-validator-context-path';
	public const string CONTEXT_KEY = 'lexeme-terms-validator-context-key';
	public const string CONTEXT_LIMIT = 'lexeme-terms-validator-context-limit';
	public const string CONTEXT_VALUE = 'lexeme-terms-validator-context-value';

	private ?TermList $deserializedTermList = null;

	public function __construct(
		private LexemeTermLanguageCodeValidator $languageCodeValidator,
		private int $maxLength,
	) {
	}

	public function validate( mixed $terms, string $path ): ?ValidationError {
		if ( !is_array( $terms ) || !$terms || array_is_list( $terms ) ) {
			return new ValidationError(
				self::CODE_INVALID_VALUE, [
					self::CONTEXT_PATH => $path,
					self::CONTEXT_VALUE => $terms,
				],
			);
		}

		$deserializedTerms = [];
		foreach ( $terms as $languageCode => $text ) {
			$languageCode = (string)$languageCode;
			if ( !$this->languageCodeValidator->isValid( $languageCode ) ) {
				return new ValidationError(
					self::CODE_INVALID_KEY, [
						self::CONTEXT_PATH => $path,
						self::CONTEXT_KEY => $languageCode,
					],
				);
			}
			$error = $this->validateText( $text, "$path/$languageCode" );
			if ( $error ) {
				return $error;
			}
			// @phan-suppress-next-line PhanTypeMismatchArgumentInternalReal $text is a validated string by now
			$deserializedTerms[] = new Term( $languageCode, trim( $text ) );
		}

		$this->deserializedTermList = new TermList( $deserializedTerms );
		return null;
	}

	private function validateText( mixed $text, string $path ): ?ValidationError {
		if ( !is_string( $text ) ) {
			return new ValidationError(
				self::CODE_INVALID_VALUE, [
				self::CONTEXT_VALUE => $text,
				self::CONTEXT_PATH => $path,
				],
			);
		}
		$trimmedText = trim( $text );
		if ( $trimmedText === '' || preg_match( '/[\v\t]/u', $trimmedText ) ) {
			return new ValidationError(
				self::CODE_INVALID_VALUE, [
					self::CONTEXT_VALUE => $text,
					self::CONTEXT_PATH => $path,
				],
			);
		}
		if ( mb_strlen( $trimmedText ) > $this->maxLength ) {
			return new ValidationError(
				self::CODE_VALUE_TOO_LONG, [
					self::CONTEXT_PATH => $path,
					self::CONTEXT_LIMIT => $this->maxLength,
				],
			);
		}

		return null;
	}

	public function getValidatedTerms(): TermList {
		if ( $this->deserializedTermList === null ) {
			throw new LogicException( 'getValidatedTerms() called before validate()' );
		}
		return $this->deserializedTermList;
	}

}
