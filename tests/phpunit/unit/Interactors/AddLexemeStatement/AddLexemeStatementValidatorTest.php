<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeStatement;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementRequest;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\TagsRetriever;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementValidator
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementValidatorTest extends MediaWikiUnitTestCase {

	private const ALLOWED_TAG = 'allowed tag';

	private const MAX_COMMENT_LENGTH = 42;

	private const VALID_STATEMENT = [
		'property' => [ 'id' => 'P123' ],
		'value' => [ 'type' => 'novalue' ],
	];

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidEditMetadata_passes(): void {
		$this->newValidator()->validate( self::newRequest( [ self::ALLOWED_TAG ], true, 'user comment' ) );
	}

	public function testGivenInvalidTag_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate(
				self::newRequest( [ self::ALLOWED_TAG, 'bad tag' ], false, null )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( "Invalid value at '/tags/1'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/tags/1' ], $e->context );
		}
	}

	public function testGivenCommentTooLong_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate(
				self::newRequest( [], false, str_repeat( 'x', self::MAX_COMMENT_LENGTH + 1 ) )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::VALUE_TOO_LONG, $e->errorCode );
			$this->assertSame( 'The input value is too long', $e->errorMessage );
			$this->assertSame(
				[ UseCaseError::CONTEXT_PATH => '/comment', UseCaseError::CONTEXT_LIMIT => self::MAX_COMMENT_LENGTH ],
				$e->context
			);
		}
	}

	private static function newRequest( array $editTags, bool $isBot, ?string $comment ): AddLexemeStatementRequest {
		return new AddLexemeStatementRequest( 'L1', self::VALID_STATEMENT, $editTags, $isBot, $comment );
	}

	private function newValidator(): AddLexemeStatementValidator {
		return new AddLexemeStatementValidator(
			new class( [ self::ALLOWED_TAG ] ) implements TagsRetriever {
				public function __construct( private array $allowedTags ) {
				}

				public function getAllowedTags(): array {
					return $this->allowedTags;
				}
			},
			self::MAX_COMMENT_LENGTH
		);
	}

}
