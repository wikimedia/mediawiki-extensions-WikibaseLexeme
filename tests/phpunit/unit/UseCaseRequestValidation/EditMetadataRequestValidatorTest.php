<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\UseCaseRequestValidation;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\Validation\TagsRetriever;

/**
 * @covers \Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator
 *
 * @license GPL-2.0-or-later
 */
class EditMetadataRequestValidatorTest extends MediaWikiUnitTestCase {

	private const ALLOWED_TAG = 'allowed tag';

	private const MAX_COMMENT_LENGTH = 42;

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidEditMetadata_passes(): void {
		$this->newValidator()->validate( [ self::ALLOWED_TAG ], 'user comment' );
	}

	public function testGivenInvalidTag_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate( [ self::ALLOWED_TAG, 'bad tag' ], null );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( "Invalid value at '/tags/1'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/tags/1' ], $e->context );
		}
	}

	public function testGivenCommentTooLong_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate( [], str_repeat( 'x', self::MAX_COMMENT_LENGTH + 1 ) );
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

	private function newValidator(): EditMetadataRequestValidator {
		return new EditMetadataRequestValidator(
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
