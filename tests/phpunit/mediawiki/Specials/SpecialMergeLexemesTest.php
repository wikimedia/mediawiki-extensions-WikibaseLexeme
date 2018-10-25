<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use Exception;
use FauxRequest;
use HamcrestPHPUnitIntegration;
use PermissionsError;
use PHPUnit\Framework\MockObject\MockObject;
use RawMessage;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\MergeLexemes\MergeLexemesInteractor;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\MediaWiki\Specials\SpecialMergeLexemes;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Tests\NewItem;
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

	/** @var WikibaseRepo */
	private $repo;

	/** @var ExceptionLocalizer|MockObject */
	private $exceptionLocalizer;

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'page';

		$this->mergeInteractor = $this->newMockMergeInteractor();
		$this->repo = WikibaseRepo::getDefaultInstance();
		$this->entityStore = $this->repo->getEntityStore();
		$this->titleLookup = $this->repo->getEntityTitleLookup();
		$this->exceptionLocalizer = $this->newMockExceptionLocalizer();
	}

	public function testSpecialMergeLexemesContainsInputFields() {
		list( $output, ) = $this->executeSpecialPage();

		$this->assertThatHamcrest(
			$output,
			is( htmlPiece( both(
				havingChild( tagMatchingOutline( '<input name="from-id">' ) )
			)->andAlso( havingChild( tagMatchingOutline( '<input name="to-id">' ) ) )
			) )
		);
	}

	public function testRequestByUserWithoutPermission_accessIsDenied() {
		$this->setMwGlobals( [
			'wgGroupPermissions' => [
				'*' => [
					'lexeme-merge' => false
				]
			]
		] );

		try {
			$this->executeSpecialPage();
			$this->fail();
		} catch ( PermissionsError $exception ) {
			$this->assertSame( 'badaccess-group0', $exception->errors[0][0] );
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

		$this->mergeInteractor = WikibaseLexemeServices::globalInstance()->newMergeLexemesInteractor( false );

		$output = $this->executeSpecialPageWithIds(
			$this->source->getId()->getSerialization(),
			$this->target->getId()->getSerialization()
		);

		$this->assertThatHamcrest(
			$output,
			is( htmlPiece( havingChild(
				both( withTagName( 'p' ) )
					->andAlso( havingTextContents(
						equalToIgnoringWhiteSpace( 'Lexeme:L1 was merged into Lexeme:L2 and redirected.' )
					) )
			) ) )
		);

		/** @var Lexeme $postMergeTarget */
		$postMergeTarget = WikibaseRepo::getDefaultInstance()
			->getEntityLookup()
			->getEntity( $this->target->getId() );

		$this->assertEquals(
			$this->source->getLemmas()->getByLanguage( 'en' ),
			$postMergeTarget->getLemmas()->getByLanguage( 'en' )
		);
		$this->assertEquals(
			$this->target->getLemmas()->getByLanguage( 'en-gb' ),
			$postMergeTarget->getLemmas()->getByLanguage( 'en-gb' )
		);
	}

	public function testGivenNotALexemeIdSerialization_showsErrorMessage() {
		$output = $this->executeSpecialPageWithIds( 'not-a-lexeme-id', 'L123' );

		$this->assertShowsErrorWithMessage(
			$output,
			wfMessage( 'wikibase-lexeme-mergelexemes-error-invalid-id',
				[
					'not-a-lexeme-id'
				] )
		);
	}

	public function testGivenTitleLookupThrows_exceptionIsLocalized() {
		$l123 = new LexemeId( 'L123' );
		$this->titleLookup = $this->newMockTitleLookup();
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
		$this->exceptionLocalizer = $this->newMockExceptionLocalizer();
		$mockException = $this->createMock( MergingException::class );
		$mockException->expects( $this->once() )
			->method( 'getErrorMessage' )
			->willReturn( new RawMessage( $expectedErrorMsg ) );
		$this->mergeInteractor = $this->newMockMergeInteractor();
		$this->mergeInteractor->method( 'mergeLexemes' )
			->willThrowException( $mockException );

		$output = $this->executeSpecialPageWithIds( 'L111', 'L222' );
		$this->assertShowsErrorWithMessage(
			$output,
			$expectedErrorMsg
		);
	}

	private function saveEntity( EntityDocument $entity ) {
		$this->entityStore->saveEntity( $entity, self::class, $this->getTestUser()->getUser() );
	}

	protected function newSpecialPage() {
		return new SpecialMergeLexemes(
			$this->mergeInteractor,
			$this->titleLookup,
			$this->exceptionLocalizer
		);
	}

	private function newMockMergeInteractor() {
		return $this->createMock( MergeLexemesInteractor::class );
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

	private function newMockTitleLookup() {
		return $this->createMock( EntityTitleLookup::class );
	}

	/**
	 * @param string $source
	 * @param string $target
	 *
	 * @return string $output
	 */
	private function executeSpecialPageWithIds( $source, $target ) {
		list( $output, ) = $this->executeSpecialPage(
			'',
			new FauxRequest( [
				'from-id' => $source,
				'to-id' => $target,
			], true )
		);

		return $output;
	}

	private function newMockExceptionLocalizer() {
		return $this->createMock( ExceptionLocalizer::class );
	}

}
