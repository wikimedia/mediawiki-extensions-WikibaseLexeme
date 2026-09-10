<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeStatement;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementRequest;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementValidator
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_STATEMENT = [
		'property' => [ 'id' => 'P123' ],
		'value' => [ 'type' => 'novalue' ],
	];

	public function testGivenValidRequest_validatesEditMetadata(): void {
		$editTags = [ 'allowed tag' ];
		$comment = 'user comment';
		$editMetadataRequestValidator = $this->createMock( EditMetadataRequestValidator::class );
		$editMetadataRequestValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $editTags, $comment );

		( new AddLexemeStatementValidator( $editMetadataRequestValidator ) )
			->validate( self::newRequest( $editTags, $comment ) );
	}

	private static function newRequest( array $editTags, ?string $comment ): AddLexemeStatementRequest {
		return new AddLexemeStatementRequest( 'L1', self::VALID_STATEMENT, $editTags, false, $comment );
	}

}
