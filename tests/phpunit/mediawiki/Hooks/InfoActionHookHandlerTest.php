<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks;

use IContextSource;
use PageProps;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use RequestContext;
use Title;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Actions\InfoActionHookHandler;
use Wikibase\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @covers \Wikibase\Repo\Hooks\InfoActionHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class InfoActionHookHandlerTest extends TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle(
		array $expected,
		IContextSource $context,
		array $pageProps
	) {
		$hookHandler = $this->newHookHandler( $context, $pageProps );
		$pageInfo = $hookHandler->handle( $context, [ 'header-basic' => [] ] );

		$this->assertEquals( $expected, $pageInfo );
	}

	public function handleProvider() {
		$context = $this->getContext();

		return [
			'some sense and forms' => [
				[
					'header-basic' => [
						[ '(wikibase-pageinfo-wbl-forms)', '5' ],
						[ '(wikibase-pageinfo-wbl-senses)', '4' ]
					],
				],
				$context,
				[ 'wbl-forms' => 5, 'wbl-senses' => 4 ]
			],
			'no sense or forms' => [
				[
					'header-basic' => [],
				],
				$context,
				[]
			]
		];
	}

	/**
	 * @param string[] $subscriptions
	 * @param IContextSource $context
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( IContextSource $context, array $pagePropsValues ) {
		$lexemeId = new LexemeId( 'L4' );

		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $context->getTitle() )
			->will( $this->returnValue( $lexemeId ) );

		$pageProps = $this->getMockBuilder( PageProps::class )
			->disableOriginalConstructor()
			->getMock();
		$pageProps->expects( $this->once() )
			->method( 'getProperties' )
			->with( $context->getTitle() )
			->willReturn( [ 1234 => $pagePropsValues ] );

		return new InfoActionHookHandler(
			new EntityNamespaceLookup( [ Lexeme::ENTITY_TYPE => NS_MAIN ] ),
			$entityIdLookup,
			$pageProps,
			$context
		);
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = $this->createMock( Title::class );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'L4' ) );

		$context = new RequestContext();
		$context->setTitle( $title );
		$context->setLanguage( 'qqx' );

		return $context;
	}

}
