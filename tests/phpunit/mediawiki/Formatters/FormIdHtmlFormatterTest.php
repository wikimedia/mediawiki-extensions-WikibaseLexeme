<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use HamcrestPHPUnitIntegration;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWikiLangTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\Formatters\FormIdHtmlFormatter;
use Wikibase\Lexeme\Presentation\Formatters\RedirectedLexemeSubEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Presentation\Formatters\FormIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class FormIdHtmlFormatterTest extends MediaWikiLangTestCase {

	use HamcrestPHPUnitIntegration;

	private const REPRESENTATION_SEPARATOR = '-S-';

	private EntityRevisionLookup $revisionLookup;
	private LabelDescriptionLookup $labelLookup;
	private EntityTitleLookup $titleLookup;
	private LocalizedTextProvider $textProvider;
	private RedirectedLexemeSubEntityIdHtmlFormatter $redirectedLexemeSubEntityIdHtmlFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->labelLookup = $this->createMock( LabelDescriptionLookup::class );
		$this->titleLookup = $this->createMock( EntityTitleLookup::class );
		$this->textProvider = $this->getMockTextProvider();
		$this->redirectedLexemeSubEntityIdHtmlFormatter =
			$this->createMock( RedirectedLexemeSubEntityIdHtmlFormatter::class );
	}

	private function makeTitleLookupReturnMainPage( FormId $expectedFormId ): void {
		$title = $this->createMock( Title::class );
		$title->method( 'isLocal' )->willReturn( true );
		$title->method( 'getLinkUrl' )->willReturn( 'LOCAL-URL#FORM' );

		$this->titleLookup->method( 'getTitleForId' )
			->with( $expectedFormId )
			->willReturn( $title );
	}

	private function getMockTextProvider(): LocalizedTextProvider {
		$mock = $this->createMock( LocalizedTextProvider::class );

		$getMethodValuesMap = [
			[
				'wikibaselexeme-formidformatter-separator-multiple-representation',
				[],
				self::REPRESENTATION_SEPARATOR,
			],
			[ 'wikibaselexeme-formidformatter-separator-grammatical-features', [], ', ' ],
			[
				'wikibaselexeme-formidformatter-link-title',
				[ 'L999-F666', 'noun, verb' ],
				'L999-F666: noun, verb',
			],
		];
		$mock->method( $this->logicalOr( 'get', 'getEscaped' ) )
			->willReturnMap( $getMethodValuesMap );
		return $mock;
	}

	public function testNonExistingFormatterIsCalledForNonExistingIds_noRevision(): void {
		$formId = new FormId( 'L999-F666' );

		/** @var EntityRevisionLookup|MockObject $revisionLookup */
		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $formId )
			->willReturn( null );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'L999-F666 <span class="wb-entity-undefinedinfo">(Deleted Form)</span>',
			$result
		);
	}

	public function testRedirectedLexemeSubEntityIdHtmlFormatterIsCalledForRedirectedLexemes(): void {
		$formId = new FormId( 'L999-F666' );

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $formId )
			->willThrowException(
				new UnresolvedEntityRedirectException(
					$formId,
					new LexemeId( 'L1000' )
				)
			);

		$this->redirectedLexemeSubEntityIdHtmlFormatter
			->expects( $this->once() )
			->method( 'formatEntityId' )
			->with( $formId )
			->willReturn( '<a href="http://url.for/Lexeme:L999#L999-F666">L999-F666</a>' );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'<a href="http://url.for/Lexeme:L999#L999-F666">L999-F666</a>',
			$result
		);
	}

	public function testNonExistingFormatterIsCalledForNonExistingIds_noTitle(): void {
		$formId = new FormId( 'L999-F666' );

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $formId )
			->willReturn( new EntityRevision(
				new Form( $formId, new TermList( [ new Term( 'en', 'a' ) ] ), []
				) ) );

		$this->titleLookup->method( 'getTitleForId' )
			->with( $formId )
			->willReturn( null );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'L999-F666 <span class="wb-entity-undefinedinfo">(Deleted Form)</span>',
			$result
		);
	}

	/**
	 * @dataProvider representationLanguageProvider
	 */
	public function testFormatId_oneRepresentation(
		string $representationLanguage,
		string $langAttr,
		string $dirAttr
	): void {
		$formId = new FormId( 'L999-F666' );

		$representationText = 'fOo';
		$formRevision = new EntityRevision(
			new Form(
				$formId,
				new TermList( [ new Term( $representationLanguage, $representationText ) ] ),
				[]
			)
		);

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $formId )
			->willReturn( $formRevision );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingRootElement(
				both( tagMatchingOutline( '<a href="LOCAL-URL#FORM" title="L999-F666">' ) )
					->andAlso( havingChild(
						both( havingTextContents( $representationText ) )
							->andAlso( tagMatchingOutline(
								"<span lang='$langAttr' dir='$dirAttr'>"
							) )
					) )
			) ) )
		);
	}

	public static function representationLanguageProvider() {
		yield 'BCP 47 compliant language code' => [ 'en', 'en', 'ltr' ];
		yield 'mediawiki language code mapped to BCP 47' => [ 'mo', 'ro-Cyrl-MD', 'ltr' ];
		yield 'rtl language' => [ 'he', 'he', 'rtl' ];
	}

	public function testFormatId_multipleRepresentations(): void {
		$formId = new FormId( 'L999-F666' );

		$representation1Language = 'pt';
		$representation1Text = 'fOo';
		$representation2Language = 'en';
		$representation2Text = 'bAr';

		$representations = new TermList( [
			new Term( $representation1Language, $representation1Text ),
			new Term( $representation2Language, $representation2Text ),
		] );
		$formRevision = new EntityRevision(
			new Form( $formId, $representations, [] )
		);

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $formId )
			->willReturn( $formRevision );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingRootElement(
				allOf(
					tagMatchingOutline( '<a href="LOCAL-URL#FORM" title="L999-F666">' ) ),
					havingChild(
						both( havingTextContents( $representation1Text ) )
							->andAlso( tagMatchingOutline( "<span lang='$representation1Language'>" ) )
					),
				havingChild(
						both( havingTextContents( $representation2Text ) )
							->andAlso( tagMatchingOutline( "<span lang='$representation2Language'>" ) )
					)

			) ) )
		);
		$this->assertEquals(
			$representation1Text . self::REPRESENTATION_SEPARATOR . $representation2Text,
			strip_tags( $result )
		);
	}

	public function testFormatId_htmlEscapesRepresentation(): void {
		$formId = new FormId( 'L999-F666' );

		$formRevision = new EntityRevision( new Form( $formId, new TermList( [
			new Term( 'pt', '<script>alert("hi")</script>' ),
		] ), [] ) );

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $formId )
			->willReturn( $formRevision );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild(
				havingTextContents( '<script>alert("hi")</script>' )
			) ) )
		);
	}

	public function testFormatId_addsIdAndGramaticalFeaturesToTitleAttribute(): void {
		$formId = new FormId( 'L999-F666' );

		$grammaticalFeature1 = new ItemId( 'Q123' );
		$grammaticalFeature2 = new ItemId( 'Q321' );

		$formRevision = new EntityRevision( new Form( $formId, new TermList( [
			new Term( 'pt', 'some representation' ),
		] ), [ $grammaticalFeature1, $grammaticalFeature2 ] ) );

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $formId )
			->willReturn( $formRevision );

		$this->labelLookup->method( 'getLabel' )->willReturnMap( [
				[ $grammaticalFeature1, new Term( 'en', 'noun' ) ],
				[ $grammaticalFeature2, new Term( 'en', 'verb' ) ],
			] );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingRootElement(
				tagMatchingOutline( '<a href="LOCAL-URL#FORM" title="L999-F666: noun, verb">' )
			) ) )
		);
	}

	public function testFormatId_exceptionOnInvalidEntity(): void {
		$nonFormId = new ItemId( 'Q99' );
		$this->expectException( InvalidArgumentException::class );
		$this->newFormIdHtmlFormatter()->formatEntityId( $nonFormId );
	}

	private function newFormIdHtmlFormatter(): FormIdHtmlFormatter {
		return new FormIdHtmlFormatter(
			$this->revisionLookup,
			$this->labelLookup,
			$this->titleLookup,
			$this->textProvider,
			$this->redirectedLexemeSubEntityIdHtmlFormatter,
			MediaWikiServices::getInstance()->getLanguageFactory()
		);
	}

}
