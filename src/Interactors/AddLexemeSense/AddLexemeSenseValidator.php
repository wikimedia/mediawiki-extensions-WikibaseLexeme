<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeSense;

use LogicException;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseValidator {

	private ?LexemeId $lexemeId = null;
	private ?BlankSense $sense = null;

	public function __construct(
		private LexemeIdValidator $lexemeIdValidator,
		private LexemeTermsValidator $lexemeTermsValidator,
		private EditMetadataRequestValidator $editMetadataRequestValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( AddLexemeSenseRequest $request ): void {
		$this->lexemeId = $this->lexemeIdValidator->validate( $request->lexemeId );

		$serialization = $request->sense;

		if ( !array_key_exists( 'glosses', $serialization ) ) {
			throw UseCaseError::newMissingField( '/sense', 'glosses' );
		}

		$sense = new BlankSense();
		$sense->getGlosses()->addAll( $this->lexemeTermsValidator->validateAndDeserialize(
			$serialization['glosses'],
			'/sense/glosses',
		) );

		$this->sense = $sense;

		$this->editMetadataRequestValidator->validate( $request->editTags, $request->comment );
	}

	public function getValidatedLexemeId(): LexemeId {
		if ( $this->lexemeId === null ) {
			throw new LogicException( 'Must not call getValidatedLexemeId() before validate()' );
		}

		return $this->lexemeId;
	}

	public function getValidatedSense(): BlankSense {
		if ( $this->sense === null ) {
			throw new LogicException( 'Must not call getValidatedSense() before validateAndDeserialize()' );
		}

		return $this->sense;
	}

}
