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

		$this->assertSame(
			$expectedResult,
			$this->newChecker( $mwUser, $permissionStatus, $userFactory )->canCreateLexeme( $user )
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

		$this->assertSame(
			$expectedResult,
			$this->newChecker( $mwUser, $permissionStatus, $userFactory )->canCreateLexeme( User::newAnonymous() )
		);
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

	private function newChecker(
		MediaWikiUser $mwUser,
		PermissionStatus $permissionStatus,
		UserFactory $userFactory
	): WikibaseEntityPermissionChecker {
		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntity' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, new Lexeme() )
			->willReturn( $permissionStatus );

		return new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );
	}

	private static function newBlockedStatus( BlockTarget $blockTarget ): PermissionStatus {
		$block = new SystemBlock();
		$block->setTarget( $blockTarget );

		$status = PermissionStatus::newEmpty();
		$status->setBlock( $block );

		return $status;
	}

}
