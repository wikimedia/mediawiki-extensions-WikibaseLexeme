<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Infrastructure;

use Generator;
use MediaWiki\Block\AnonIpBlockTarget;
use MediaWiki\Block\AutoBlockTarget;
use MediaWiki\Block\BlockTarget;
use MediaWiki\Block\RangeBlockTarget;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Block\UserBlockTarget;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\User\User as MediaWikiUser;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\PermissionCheckResult;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Infrastructure\WikibaseEntityPermissionChecker;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Lexeme\Infrastructure\WikibaseEntityPermissionChecker
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityPermissionCheckerTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider permissionStatusProvider
	 */
	public function testCanCreateLexemeAsRegisteredUser(
		PermissionStatus $permissionStatus,
		PermissionCheckResult $expectedResult
	): void {
		$user = User::withUsername( 'user123' );

		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newFromName' )
			->with( 'user123' )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntity' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, new Lexeme() )
			->willReturn( $permissionStatus );
		$newChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertSame(
			$expectedResult,
			$newChecker->canCreateLexeme( $user )
		);
	}

	/**
	 * @dataProvider permissionStatusProvider
	 */
	public function testCanCreateLexemeAsAnonymousUser(
		PermissionStatus $permissionStatus,
		PermissionCheckResult $expectedResult
	): void {
		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newAnonymous' )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntity' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, new Lexeme() )
			->willReturn( $permissionStatus );
		$newChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertSame(
			$expectedResult,
			$newChecker->canCreateLexeme( User::newAnonymous() )
		);
	}

	/**
	 * @dataProvider permissionStatusProvider
	 */
	public function testCanEditLexemeAsRegisteredUser(
		PermissionStatus $permissionStatus,
		PermissionCheckResult $expectedResult
	): void {
		$user = User::withUsername( 'potato' );
		$lexemeId = new LexemeId( 'L123' );

		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newFromName' )
			->with( $user->getUsername() )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntityId' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, $lexemeId )
			->willReturn( $permissionStatus );
		$newChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertEquals(
			$expectedResult,
			$newChecker->canEditLexeme( $user, $lexemeId )
		);
	}

	/**
	 * @dataProvider permissionStatusProvider
	 */
	public function testCanEditAsAnonymousUser(
		PermissionStatus $permissionStatus,
		PermissionCheckResult $result
	): void {
		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newAnonymous' )
			->willReturn( $mwUser );
		$lexemeId = new LexemeId( 'L123' );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntityId' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, $lexemeId )
			->willReturn( $permissionStatus );

		$newChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertEquals( $result, $newChecker->canEditLexeme( User::newAnonymous(), $lexemeId ) );
	}

	public static function permissionStatusProvider(): Generator {
		yield 'good status' => [ PermissionStatus::newGood(), PermissionCheckResult::ALLOWED ];

		yield 'denied, unknown reason' => [
			PermissionStatus::newFatal( 'insufficient permissions' ),
			PermissionCheckResult::DENIED_UNKNOWN_REASON,
		];

		yield 'denied, user blocked' => [
			self::newBlockedStatus( new UserBlockTarget( new UserIdentityValue( 0, 'test' ) ) ),
			PermissionCheckResult::USER_BLOCKED,
		];

		yield 'denied, ip blocked' => [
			self::newBlockedStatus( new AnonIpBlockTarget( '1.2.3.4' ) ),
			PermissionCheckResult::IP_BLOCKED,
		];

		yield 'denied, ip range blocked' => [
			self::newBlockedStatus( new RangeBlockTarget( '1.2.3.4/16', [] ) ),
			PermissionCheckResult::IP_BLOCKED,
		];

		yield 'denied, auto-blocked' => [
			self::newBlockedStatus( new AutoBlockTarget( 0 ) ),
			PermissionCheckResult::IP_BLOCKED,
		];
	}

	private static function newBlockedStatus( BlockTarget $blockTarget ): PermissionStatus {
		$block = new SystemBlock();
		$block->setTarget( $blockTarget );

		$status = PermissionStatus::newEmpty();
		$status->setBlock( $block );

		return $status;
	}

}
