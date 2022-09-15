<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\ChangeOp\Validation;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\MediaWiki\Api\Error\InvalidItemId;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeTermLanguageCanNotBeEmpty;
use Wikibase\Lexeme\MediaWiki\Api\Error\UnknownLanguage;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermLanguageValidator {

	/**
	 * According to BCP 47 (https://tools.ietf.org/html/bcp47)
	 */
	private const PRIVATE_USE_SUBTAG_SEPARATOR = '-x-';

	/**
	 * @var ContentLanguages
	 */
	private $languages;

	public function __construct( ContentLanguages $languages ) {
		$this->languages = $languages;
	}

	/**
	 * @param string $input
	 * @param ValidationContext $context
	 * @param string|null $termText for context in the error message, if available
	 */
	public function validate( $input, ValidationContext $context, $termText = null ) {
		if ( !is_string( $input ) ) {
			$context->addViolation( new JsonFieldHasWrongType( 'string', gettype( $input ) ) );
			return;
		}

		$parts = explode(
			self::PRIVATE_USE_SUBTAG_SEPARATOR,
			$input,
			2
		);
		$language = $parts[0];

		if ( $language === '' ) {
			$context->addViolation( new LexemeTermLanguageCanNotBeEmpty() );
			return;
		}

		if ( !$this->languages->hasLanguage( $language ) ) {
			$context->addViolation( new UnknownLanguage( $language, $termText ) );
		}

		if ( count( $parts ) > 1 && !$this->isValidItemId( $parts[1] ) ) {
			$context->addViolation( new InvalidItemId( $parts[1] ) );
		}
	}

	private function isValidItemId( $id ) {
		return preg_match( ItemId::PATTERN, $id ) &&
			strtoupper( $id ) === $id;
	}

}
