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
		$originalLexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			nextFormId: 7,
			nextSenseId: 5,
		);

		$patchedSerialization = [
			'lemmas' => [ 'en' => 'colour', 'en-gb' => 'colour' ],
			'lexical_category' => 'Q1',
			'language' => 'Q2',
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
		$originalLexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		);

		try {
			( new PatchedLexemeValidator() )->validateAndDeserialize( $patchedSerialization, $originalLexeme );
			$this->fail( 'Expected a missing field error' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError->errorCode, $e->errorCode );
			$this->assertSame( $expectedError->context, $e->context );
		}
	}

	public static function provideMissingFields(): Generator {
		$serialization = [
			'id' => 'L1',
			'lemmas' => [ 'en' => 'color' ],
			'lexical_category' => 'Q1',
			'language' => 'Q2',
			'statements' => [],
			'forms' => [],
			'senses' => [],
		];

		foreach ( [ 'lemmas', 'lexical_category', 'language' ] as $field ) {
			$patchedSerialization = $serialization;
			unset( $patchedSerialization[$field] );

			yield "missing '$field' field" => [
				$patchedSerialization,
				UseCaseError::newMissingFieldInPatchResult( '', $field ),
			];
		}
	}

}
