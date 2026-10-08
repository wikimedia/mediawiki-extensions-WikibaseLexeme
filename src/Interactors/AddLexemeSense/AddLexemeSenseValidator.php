<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeSense;

use LogicException;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseValidator {

	private ?BlankSense $sense = null;

	public function __construct(
		private LexemeTermsValidator $lexemeTermsValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( AddLexemeSenseRequest $request ): void {
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
	}

	public function getValidatedSense(): BlankSense {
		if ( $this->sense === null ) {
			throw new LogicException( 'Must not call getValidatedSense() before validateAndDeserialize()' );
		}

		return $this->sense;
	}

}
