<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\PatchLexeme;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchedLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @covers \Wikibase\Lexeme\Interactors\PatchLexeme\PatchedLexemeValidator
 *
 * @license GPL-2.0-or-later
 */
class PatchedLexemeValidatorTest extends TestCase {

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

		$patchedLexeme = ( new PatchedLexemeValidator() )->validateAndDeserialize(
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
			( new PatchedLexemeValidator() )->validateAndDeserialize( $patchedSerialization, $originalLexeme );
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
	 */
	public function testGivenIllegalModification_throws(
		Lexeme $originalLexeme,
		array $patchedSerialization,
		UseCaseError $expectedError
	): void {
		try {
			( new PatchedLexemeValidator() )->validateAndDeserialize(
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
