<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\UseCaseRequestValidation;

use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\TagsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class EditMetadataRequestValidator {

	public function __construct(
		private TagsRetriever $tagsRetriever,
		private int $maxCommentLength,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( array $editTags, ?string $comment ): void {
		$this->validateEditTags( $editTags );
		$this->validateComment( $comment );
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

}
