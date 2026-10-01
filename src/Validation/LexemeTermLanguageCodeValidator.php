<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeTermLanguageCodeValidator {

	public function isValid( string $languageCode ): bool;

}
