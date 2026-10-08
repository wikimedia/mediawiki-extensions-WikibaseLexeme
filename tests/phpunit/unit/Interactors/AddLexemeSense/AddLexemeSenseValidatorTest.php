<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeSense;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseRequest;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;
use Wikibase\Lexeme\Validation\LexemeTermLanguageCodeValidator;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseValidator
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_LANGUAGE_CODES = [ 'en', 'en-gb' ];

	public function testGivenValidRequest_exposesSense(): void {
		$enGloss = 'visual perception of light wavelengths';

		$validator = $this->newValidator();

		$validator->validate( $this->newRequest( [
			'glosses' => [ 'en' => $enGloss ],
		] ) );

		$this->assertEquals(
			new TermList( [ new Term( 'en', $enGloss ) ] ),
			$validator->getValidatedSense()->getGlosses()
		);
	}

	public function testGivenMissingGlosses_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate( $this->newRequest( [] ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( '/sense', 'glosses' ), $e );
		}
	}

	public function testGivenInvalidGlosses_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate( $this->newRequest( [ 'glosses' => [ 'en' => '' ] ] ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( '/sense/glosses/en' ), $e );
		}
	}

	public function testGivenValidateNotCalled_getValidatedSenseThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedSense();
	}

	private function newRequest( array $sense ): AddLexemeSenseRequest {
		return new AddLexemeSenseRequest( 'L1', $sense, [], false, null );
	}

	private function newValidator(): AddLexemeSenseValidator {
		return new AddLexemeSenseValidator(
			new LexemeTermsValidator(
				new class( self::VALID_LANGUAGE_CODES ) implements LexemeTermLanguageCodeValidator {
					public function __construct( private array $validLanguageCodes ) {
					}

					public function isValid( string $languageCode ): bool {
						return in_array( $languageCode, $this->validLanguageCodes );
					}
				},
				LemmaTermValidator::LEMMA_MAX_LENGTH,
			),
		);
	}

}
