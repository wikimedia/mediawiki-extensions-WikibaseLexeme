<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors;

use Generator;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\PermissionCheckResult;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\PermissionChecker;
use Wikibase\Lexeme\Interactors\AssertUserIsAuthorized;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @covers \Wikibase\Lexeme\Interactors\AssertUserIsAuthorized
 *
 * @license GPL-2.0-or-later
 */
class AssertUserIsAuthorizedTest extends MediaWikiUnitTestCase {

	public function testGivenUserIsAuthorizedToCreateALexeme_doesNotThrow(): void {
		$user = User::withUsername( 'potato' );
		$permissionChecker = $this->createMock( PermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canCreateLexeme' )
			->with( $user )
			->willReturn( PermissionCheckResult::ALLOWED );

		( new AssertUserIsAuthorized( $permissionChecker ) )->checkCreateLexemePermissions( $user );
	}

	/**
	 * @dataProvider lexemeCreationDeniedProvider
	 */
	public function testGivenUserIsUnauthorizedToCreateALexeme_throwsUseCaseError(
		PermissionCheckResult $checkResult,
		UseCaseError $expectedError
	): void {
		$permissionChecker = $this->createStub( PermissionChecker::class );
		$permissionChecker->method( 'canCreateLexeme' )->willReturn( $checkResult );

		try {
			( new AssertUserIsAuthorized( $permissionChecker ) )->checkCreateLexemePermissions( User::newAnonymous() );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function lexemeCreationDeniedProvider(): Generator {
		yield 'unknown reason' => [
			PermissionCheckResult::DENIED_UNKNOWN_REASON,
			new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				'You have no permission to create a lexeme'
			),
		];

		yield 'user blocked' => [
			PermissionCheckResult::USER_BLOCKED,
			UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED ),
		];

		yield 'ip blocked' => [
			PermissionCheckResult::IP_BLOCKED,
			UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_IP_BLOCKED ),
		];
	}

	public function testGivenUserIsAuthorizedToEditALexeme_doesNotThrow(): void {
		$lexemeId = new LexemeId( 'L1' );
		$user = User::withUsername( 'potato' );
		$permissionChecker = $this->createMock( PermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canEditLexeme' )
			->with( $user, $lexemeId )
			->willReturn( PermissionCheckResult::ALLOWED );

		( new AssertUserIsAuthorized( $permissionChecker ) )->checkEditPermissions( $user, $lexemeId );
	}

	/**
	 * @dataProvider lexemeEditPermissionDeniedProvider
	 */
	public function testGivenUserIsUnauthorizedToEditALexeme_throwsUseCaseError(
		PermissionCheckResult $checkResult,
		UseCaseError $expectedError
	): void {
		$permissionChecker = $this->createStub( PermissionChecker::class );
		$permissionChecker->method( 'canEditLexeme' )->willReturn( $checkResult );

		try {
			( new AssertUserIsAuthorized( $permissionChecker ) )
				->checkEditPermissions( User::newAnonymous(), new LexemeId( 'L123' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function lexemeEditPermissionDeniedProvider(): Generator {
		yield "page protected" => [
			PermissionCheckResult::PAGE_PROTECTED,
			UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_PAGE_PROTECTED ),
		];

		yield 'user blocked' => [
			PermissionCheckResult::USER_BLOCKED,
			UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED ),
		];

		yield 'ip blocked' => [
			PermissionCheckResult::IP_BLOCKED,
			UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_IP_BLOCKED ),
		];

		yield 'unknown reason' => [
			PermissionCheckResult::DENIED_UNKNOWN_REASON,
			new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				'You have no permission to edit this resource'
			),
		];
	}

}
