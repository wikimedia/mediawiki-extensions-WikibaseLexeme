<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\PatchLexeme;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermValueValidator;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Infrastructure\ContentLanguagesLexemeTermLanguageCodeValidator;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchedLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\LexemeTermsValidator;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers \Wikibase\Lexeme\Interactors\PatchLexeme\PatchedLexemeValidator
 *
 * @license GPL-2.0-or-later
 */
class PatchedLexemeValidatorTest extends TestCase {

	private const VALID_LANGUAGE_CODES = [ 'en-gb', 'en' ];

	public function testGivenValidPatchResult_returnsPatchedLexeme(): void {
		$originalLexeme = self::newOriginalLexeme();

		$patchedSerialization = self::newSerialization();
		$patchedSerialization[ 'lemmas' ] = [
			'en' => 'colour',
			'en-gb' => 'colour',
		];

		$expectedPatchedLexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [
				new Term( 'en', 'colour' ),
				new Term( 'en-gb', 'colour' ),
			] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			nextFormId: 7,
			nextSenseId: 5,
		);

		$patchedLexeme = $this->newValidator()->validateAndDeserialize(
			$patchedSerialization, $originalLexeme
		);

		$this->assertEquals( $expectedPatchedLexeme, $patchedLexeme );
	}

	/**
	 * @dataProvider provideMissingFields
	 */
	public function testGivenMissingField_throws(
		array $patchedSerialization,
		UseCaseError $expectedError
	): void {
		$originalLexeme = self::newOriginalLexeme();

		try {
			$this->newValidator()->validateAndDeserialize( $patchedSerialization, $originalLexeme );
			$this->fail( 'Expected a missing field error' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError->errorCode, $e->errorCode );
			$this->assertSame( $expectedError->context, $e->context );
		}
	}

	public static function provideMissingFields(): Generator {
		$serialization = self::newSerialization();

		foreach ( [ 'lemmas', 'lexical_category', 'language' ] as $field ) {
			$patchedSerialization = $serialization;
			unset( $patchedSerialization[$field] );

			yield "missing '$field' field" => [
				$patchedSerialization,
				UseCaseError::newMissingFieldInPatchResult( '', $field ),
			];
		}
	}

	/**
	 * @dataProvider provideIllegalModificationsLexemeID
	 * @dataProvider provideIllegalModificationsSenseID
	 */
	public function testGivenIllegalModification_throws(
		Lexeme $originalLexeme,
		array $patchedSerialization,
		UseCaseError $expectedError
	): void {
		try {
			$this->newValidator()->validateAndDeserialize(
				$patchedSerialization,
				$originalLexeme,
			);
			$this->fail( 'Expected a modified read-only value error' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError->errorCode, $e->errorCode );
			$this->assertSame( $expectedError->context, $e->context );
		}
	}

	public static function provideIllegalModificationsLexemeID(): Generator {
		$serialization = self::newSerialization();
		$serialization['id'] = 'L321';

		yield 'change lexeme ID' => [
			self::newOriginalLexeme(),
			$serialization,
			UseCaseError::newPatchResultModifiedReadOnlyValue( '/id' ),
		];
	}

	public function testGivenInvalidLemmas_throws(): void {
		$originalLexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		);

		$patchedSerialization = [
			'id' => 'L1',
			'lemmas' => 'invalid',
			'lexical_category' => 'Q1',
			'language' => 'Q2',
			'statements' => [],
			'forms' => [],
			'senses' => [],
		];

		try {
			$this->newValidator()->validateAndDeserialize( $patchedSerialization, $originalLexeme );
			$this->fail( 'Expected an invalid value error' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( 'patch-result-invalid-value', $e->errorCode );
			$this->assertSame( [ 'value' => 'invalid', 'path' => '/lemmas' ], $e->context );
		}
	}

	public function testGivenInvalidLemmaKey_throws(): void {
		$originalLexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		);

		$patchedSerialization = [
			'id' => 'L1',
			'lemmas' => [ 'not-a-valid-language-code' => 'color' ],
			'lexical_category' => 'Q1',
			'language' => 'Q2',
			'statements' => [],
			'forms' => [],
			'senses' => [],
		];

		try {
			$this->newValidator()->validateAndDeserialize( $patchedSerialization, $originalLexeme );
			$this->fail( 'Expected an invalid key error' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( 'patch-result-invalid-key', $e->errorCode );
			$this->assertSame( [ 'path' => '/lemmas', 'key' => 'not-a-valid-language-code' ], $e->context );
		}
	}

	public function testGivenLemmaValueTooLong_throws(): void {
		$originalLexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		);

		$patchedSerialization = [
			'id' => 'L1',
			'lemmas' => [ "en" => str_repeat( 'x', 1001 ) ],
			'lexical_category' => 'Q1',
			'language' => 'Q2',
			'statements' => [],
			'forms' => [],
			'senses' => [],
		];

		try {
			$this->newValidator()->validateAndDeserialize( $patchedSerialization, $originalLexeme );
			$this->fail( 'Expected a value too long error' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( 'patch-result-value-too-long', $e->errorCode );
			$this->assertSame( [ 'path' => '/lemmas/en', 'limit' => 1000 ], $e->context );
		}
	}

	public static function provideIllegalModificationsSenseID(): Generator {
		$originalLexeme = self::newOriginalLexeme();
		$originalLexeme->addOrUpdateSense( new Sense(
			new SenseId( 'L1-S1' ),
			new TermList( [ new Term( 'en', 'a visual property' ) ] ),
		) );

		$originalSerialization = self::newSerialization();
		$originalSerialization['senses'] = [ [
			'id' => 'L1-S1',
			'glosses' => [ 'en' => 'a visual property' ],
		] ];

		$changedExistingSenseId = $originalSerialization;
		$changedExistingSenseId['senses'][0]['id'] = 'L1-S9999';

		yield 'change existing sense ID' => [
			$originalLexeme,
			$changedExistingSenseId,
			UseCaseError::newPatchResultModifiedReadOnlyValue( '/senses/0/id' ),
		];

		$duplicateExistingSenseId = $originalSerialization;
		$duplicateExistingSenseId['senses'][] = $originalSerialization['senses'][0];

		yield 'duplicate existing sense ID' => [
			$originalLexeme,
			$duplicateExistingSenseId,
			UseCaseError::newPatchResultModifiedReadOnlyValue( '/senses/1/id' ),
		];
	}

	private function newValidator( ?LexemeTermsValidator $lexemeTermsValidator = null ): PatchedLexemeValidator {
		if ( $lexemeTermsValidator == null ) {
			$lexemeTermsValidator = new LexemeTermsValidator(
				new ContentLanguagesLexemeTermLanguageCodeValidator(
					new StaticContentLanguages( self::VALID_LANGUAGE_CODES )
				), LexemeTermValueValidator::MAX_LENGTH
			);
		}
		return new PatchedLexemeValidator( $lexemeTermsValidator );
	}

	private static function newOriginalLexeme(): Lexeme {
		return new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			nextFormId: 7,
			nextSenseId: 5,
		);
	}

	private static function newSerialization(): array {
		return [
			'id' => 'L1',
			'lemmas' => [ 'en' => 'color' ],
			'lexical_category' => 'Q1',
			'language' => 'Q2',
		];
	}

}
