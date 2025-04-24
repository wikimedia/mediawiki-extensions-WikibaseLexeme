<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use Exception;
use HamcrestPHPUnitIntegration;
use MediaWiki\Exception\PermissionsError;
use MediaWiki\Language\RawMessage;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\WebResponse;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\MediaWiki\Specials\SpecialMergeLexemes;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\AnonymousEditWarningBuilder;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Specials\SpecialMergeLexemes
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class SpecialMergeLexemesTest extends SpecialPageTestBase {
	use HamcrestPHPUnitIntegration;
	use TempUserTestTrait;

	private const TAGS = [ 'mw-replace' ];

	/** @var Lexeme */
	private $source;

	/** @var Lexeme */
	private $target;

	/** @var EntityStore */
	private $entityStore;

	/** @var \Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor */
	private $mergeInteractor;

	/** @var EntityTitleLookup */
	private $titleLookup;

	/** @var ExceptionLocalizer|MockObject */
	private $exceptionLocalizer;

	/** @var PermissionManager */
	private $permissionManager;
	private AnonymousEditWarningBuilder $anonymousEditWarningBuilder;

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );

		$mwServices = MediaWikiServices::getInstance();

		$this->mergeInteractor = $this->createMock( MergeLexemesInteractor::class );
		$this->entityStore = WikibaseRepo::getEntityStore();
		$this->titleLookup = WikibaseRepo::getEntityTitleLookup( $mwServices );
		$this->exceptionLocalizer = $this->createMock( ExceptionLocalizer::class );
		$this->permissionManager = $mwServices->getPermissionManager();
		$this->anonymousEditWarningBuilder = WikibaseRepo::getAnonymousEditWarningBuilder();
	}

	public function testSpecialMergeLexemesContainsInputFields() {
		[ $output ] = $this->executeSpecialPage();

		$this->assertThatHamcrest(
			$output,
			is( htmlPiece( both(
				havingChild( tagMatchingOutline( '<input name="from-id">' ) )
			)->andAlso( havingChild( tagMatchingOutline( '<input name="to-id">' ) ) )
			) )
		);
	}

	public function testSuccessMessageShown(): void {
		[ $output ] = $this->executeSpecialPage( null, new FauxRequest( [
			'from-id' => 'L1',
			'to-id' => 'L2',
			'success' => '1',
		] ) );

		$this->assertThatHamcrest(
			$output,
			is( htmlPiece( havingChild(
				both( withTagName( 'p' ) )
					->andAlso( havingTextContents(
						containsString( '(wikibase-lexeme-mergelexemes-success: ' )
					) )
			) ) )
		);
	}

	public function testRequestByUserWithoutPermission_accessIsDenied() {
		$this->overrideConfigValue( MainConfigNames::GroupPermissions, [
			'*' => [
				'lexeme-merge' => false,
			],
		] );

		try {
			$this->executeSpecialPage();
			$this->fail();
		} catch ( PermissionsError $exception ) {
			$this->assertSame( 'badaccess-group0', $exception->getMessageObject()->getKey() );
		}
	}

	public function testGivenMergeSucceeds_showsSuccessMessage() {
		$language = NewItem::withId( 'Q1' )->build();
		$lexCat = NewItem::withId( 'Q2' )->build();
		$this->source = NewLexeme::havingId( 'L1' )
			->withLanguage( $language->getId() )
			->withLexicalCategory( $lexCat->getId() )
			->withLemma( 'en', 'color' )
			->build();
		$this->target = NewLexeme::havingId( 'L2' )
			->withLanguage( $language->getId() )
			->withLexicalCategory( $lexCat->getId() )
			->withLemma( 'en-gb', 'colour' )
			->build();

		$this->saveEntity( $language );
		$this->saveEntity( $lexCat );
		$this->saveEntity( $this->source );
		$this->saveEntity( $this->target );

		$this->mergeInteractor = WikibaseLexemeServices::getMergeLexemesInteractor();

		$output = $this->executeSpecialPageWithIds(
			$this->source->getId()->getSerialization(),
			$this->target->getId()->getSerialization()
		);

		$this->assertThatHamcrest(
			$output,
			is( htmlPiece( havingChild(
				both( withTagName( 'p' ) )
					->andAlso( havingTextContents(
						containsString( '(wikibase-lexeme-mergelexemes-success: ' )
					) )
			) ) )
		);

		/** @var Lexeme $postMergeTarget */
		$postMergeTarget = WikibaseRepo::getEntityLookup()
			->getEntity( $this->target->getId() );

		$this->assertEquals(
			$this->source->getLemmas()->getByLanguage( 'en' ),
			$postMergeTarget->getLemmas()->getByLanguage( 'en' )
		);
		$this->assertEquals(
			$this->target->getLemmas()->getByLanguage( 'en-gb' ),
			$postMergeTarget->getLemmas()->getByLanguage( 'en-gb' )
		);

		$changeTagsStore = $this->getServiceContainer()->getChangeTagsStore();

		$entityTitleStoreLookup = WikibaseRepo::getEntityTitleStoreLookup();
		$titles = $entityTitleStoreLookup->getTitlesForIds( [
			$this->source->getId(),
			$this->target->getId(),
		] );
		$targetTitle = $titles[$this->target->getId()->getSerialization()];
		$targetTags = $changeTagsStore->getTags( $this->db, null, $targetTitle->getLatestRevID() );
		$this->assertArrayEquals( self::TAGS, $targetTags );
		$sourceTitle = $titles[$this->source->getId()->getSerialization()];
		$sourceTags = $changeTagsStore->getTags( $this->db, null, $sourceTitle->getLatestRevID() );
		$this->assertArrayEquals( array_merge( self::TAGS, [ 'mw-new-redirect' ] ), $sourceTags );
	}

	public function testGivenNotALexemeIdSerialization_showsErrorMessage() {
		$output = $this->executeSpecialPageWithIds( 'not-a-lexeme-id', 'L123' );

		$this->assertShowsErrorWithMessage(
			$output,
			'(wikibase-lexeme-mergelexemes-error-invalid-id: not-a-lexeme-id)'
		);
	}

	public function testGivenTitleLookupThrows_exceptionIsLocalized() {
		$l123 = new LexemeId( 'L123' );
		$this->titleLookup = $this->createMock( EntityTitleLookup::class );
		$exception = new Exception();
		$expected = 'localized evil error';
		$this->titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $l123 )
			->willThrowException( $exception );
		$this->exceptionLocalizer->expects( $this->once() )
			->method( 'getExceptionMessage' )
			->with( $exception )
			->willReturn( new RawMessage( $expected ) );

		$output = $this->executeSpecialPageWithIds( $l123->getSerialization(), 'L234' );
		$this->assertShowsErrorWithMessage(
			$output,
			$expected
		);
	}

	public function testGivenMergeException_showsErrorMessage() {
		$expectedErrorMsg = 'bad things happened';
		$this->exceptionLocalizer = $this->createMock( ExceptionLocalizer::class );
		$mockException = $this->createMock( MergingException::class );
		$mockException->expects( $this->once() )
			->method( 'getErrorMessage' )
			->willReturn( new RawMessage( $expectedErrorMsg ) );
		$this->mergeInteractor = $this->createMock( MergeLexemesInteractor::class );
		$this->mergeInteractor->method( 'mergeLexemes' )
			->willThrowException( $mockException );

		$output = $this->executeSpecialPageWithIds( 'L111', 'L222' );
		$this->assertShowsErrorWithMessage(
			$output,
			$expectedErrorMsg
		);
	}

	public function testGivenBadToken_showsErrorMessage(): void {
		$expectedErrorMsg = 'bad token happened';
		$this->exceptionLocalizer = $this->createMock( ExceptionLocalizer::class );
		$this->exceptionLocalizer->expects( $this->once() )
			->method( 'getExceptionMessage' )
			->willReturn( new RawMessage( $expectedErrorMsg ) );

		$output = $this->executeSpecialPageWithIds( 'L111', 'L222', 'bad token' );
		$this->assertShowsErrorWithMessage(
			$output,
			$expectedErrorMsg
		);
	}

	public function testGivenGetRequestWithData_showsErrorMessage(): void {
		$expectedErrorMsg = 'bad request happened';
		$this->exceptionLocalizer = $this->createMock( ExceptionLocalizer::class );
		$this->exceptionLocalizer->expects( $this->once() )
			->method( 'getExceptionMessage' )
			->willReturn( new RawMessage( $expectedErrorMsg ) );

		$output = $this->executeSpecialPageWithIds( 'L111', 'L222', null, false );
		$this->assertShowsErrorWithMessage(
			$output,
			$expectedErrorMsg
		);
	}

	public function testTempUserCreatedRedirect(): void {
		$this->enableAutoCreateTempUser();
		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'en' );

		$item = NewItem::withId( 'Q1' )->build();
		$source = NewLexeme::havingId( 'L1' )
			->withLanguage( $item->getId() )
			->withLexicalCategory( $item->getId() )
			->withLemma( 'en', 'color' )
			->build();
		$target = NewLexeme::havingId( 'L2' )
			->withLanguage( $item->getId() )
			->withLexicalCategory( $item->getId() )
			->withLemma( 'en-gb', 'colour' )
			->build();
		$this->saveEntity( $item );
		$this->saveEntity( $source );
		$this->saveEntity( $target );

		$this->mergeInteractor = WikibaseLexemeServices::getMergeLexemesInteractor();

		$request = new FauxRequest( [
			'from-id' => 'L1',
			'to-id' => 'L2',
		], true );
		$this->setTemporaryHook( 'TempUserCreatedRedirect', function (
			$session,
			$user,
			string $returnTo,
			string $returnToQuery,
			string $returnToAnchor,
			&$redirectUrl
		) {
			$this->assertSame( 'Special:MergeLexemes', $returnTo );
			$this->assertSame( 'from-id=L1&to-id=L2&success=1', $returnToQuery );
			$this->assertSame( '', $returnToAnchor );
			$redirectUrl = 'https://wiki.example/';
		} );

		/** @var WebResponse $response */
		[ , $response ] = $this->executeSpecialPage( '', $request );

		$this->assertSame( 'https://wiki.example/', $response->getHeader( 'location' ) );
	}

	private function saveEntity( EntityDocument $entity ) {
		$this->entityStore->saveEntity( $entity, self::class, $this->getTestUser()->getUser() );
	}

	protected function newSpecialPage() {
		return new SpecialMergeLexemes(
			self::TAGS,
			$this->mergeInteractor,
			WikibaseRepo::getTokenCheckInteractor( $this->getServiceContainer() ),
			$this->titleLookup,
			$this->exceptionLocalizer,
			$this->permissionManager,
			$this->anonymousEditWarningBuilder
		);
	}

	private function assertShowsErrorWithMessage( $output, $string ) {
		$this->assertThatHamcrest(
			$output,
			is( htmlPiece( havingChild(
				both( tagMatchingOutline( '<p class="error">' ) )
					->andAlso( havingTextContents( $string ) )
			) ) )
		);
	}

	/**
	 * @param string $source
	 * @param string $target
	 *
	 * @return string
	 */
	private function executeSpecialPageWithIds(
		string $source,
		string $target,
		?string $token = null,
		bool $wasPosted = true
	): string {
		$data = [
			'from-id' => $source,
			'to-id' => $target,
		];
		if ( $token !== null ) {
			$data['wpEditToken'] = $token; // otherwise filled in by default
		}

		[ $output ] = $this->executeSpecialPage(
			'',
			new FauxRequest( $data, $wasPosted )
		);
		return $output;
	}

}
