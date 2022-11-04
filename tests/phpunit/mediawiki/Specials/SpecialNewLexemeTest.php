<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use DataValues\StringValue;
use Exception;
use FauxRequest;
use Language;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\Restriction\NamespaceRestriction;
use MediaWiki\MediaWikiServices;
use PermissionsError;
use PHPUnit\Framework\MockObject\MockObject;
use RequestContext;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Specials\SpecialNewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\Tests\Specials\SpecialNewEntityTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Specials\SpecialNewLexeme
 *
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class SpecialNewLexemeTest extends SpecialNewEntityTestCase {

	private const EXISTING_ITEM_ID = 'Q1';
	private const NON_EXISTING_ITEM_ID = 'Q100';

	/**
	 * @var StatsdDataFactoryInterface|MockObject
	 */
	private $stats;

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );

		$this->tablesUsed[] = 'page';
		$this->givenItemExists( self::EXISTING_ITEM_ID );
		$this->stats = $this->createMock( StatsdDataFactoryInterface::class );
		$this->copyrightView = $this->createMock( SpecialPageCopyrightView::class );
		$this->copyrightView->method( "getHtml" )->willReturn( 'copyright' );
	}

	private function getMockSummaryFormatter(): SummaryFormatter {
		$summaryFormatter = $this->createMock( SummaryFormatter::class );

		$summaryFormatter->method( 'formatSummary' )
			->willReturnCallback( static function ( FormatableSummary $summary ) {
				return 'MOCKFORMAT: ' .
					$summary->getMessageKey() .
					' ' .
					$summary->getUserSummary();
			} );
		return $summaryFormatter;
	}

	protected function newSpecialPage(): SpecialNewLexeme {
		$summaryFormatter = $this->getMockSummaryFormatter();

		return new SpecialNewLexeme(
			self::TAGS,
			$this->copyrightView,
			$this->getServiceContainer()->getLinkRenderer(),
			$this->stats,
			WikibaseRepo::getEditEntityFactory(),
			new EntityNamespaceLookup( [ Lexeme::ENTITY_TYPE => 146 ] ),
			WikibaseRepo::getEntityTitleStoreLookup(),
			WikibaseRepo::getEntityLookup(),
			WikibaseRepo::getEntityIdParser(),
			$summaryFormatter,
			WikibaseRepo::getEntityIdHtmlLinkFormatterFactory(),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			WikibaseRepo::getValidatorErrorLocalizer(),
			WikibaseLexemeServices::getLemmaTermValidator()
		);
	}

	public function testRateLimitIsCheckedWhenEditing(): void {
		$formData = $this->provideValidEntityCreationRequests()['everything is set'][0];

		$this->setTemporaryHook(
			'PingLimiter',
			function ( User &$user, $action, &$result ) {
				$this->assertSame( 'edit', $action );
				$result = true;
				return false;
			} );

		$formData['wpEditToken'] = RequestContext::getMain()->getUser()->getEditToken();
		$request = new FauxRequest( $formData, true );

		[ $html, ] = $this->executeSpecialPage( '', $request, 'qqx' );

		$this->assertStringContainsString( '(actionthrottledtext)', $html );
	}

	/**
	 * @throws \Exception
	 */
	public function testExceptionWhenUserBlockedOnNamespace(): void {
		$user = $this->getTestBlockedUser( false, [ 146 ] );

		$this->expectException( \UserBlockedError::class );
		$this->executeSpecialPage( '', null, null, $user );
	}

	public function testNoExceptionWhenUserBlockedOnDifferentNamespace(): void {
		$user = $this->getTestBlockedUser( false, [ NS_MAIN ] );

		// to avoid test being tagged as risky for not making assertions
		$this->addToAssertionCount( 1 );
		$this->executeSpecialPage( '', null, null, $user );
	}

	/**
	 * @throws \Exception
	 */
	public function testExceptionWhenUserBlockedSitewide(): void {
		$user = $this->getTestBlockedUser( true );

		$this->stats->expects( $this->once() )
			->method( 'increment' )
			->with( 'wikibase.lexeme.special.NewLexeme.views' );
		$this->expectException( \UserBlockedError::class );
		$this->executeSpecialPage( '', null, null, $user );
	}

	private function getTestBlockedUser( $blockIsSitewide, $blockedNamespaces = null ): User {
		$user = self::getMutableTestUser()->getUser();
		$block = new DatabaseBlock( [
			'address' => $user->getName(),
			'user' => $user->getId(),
			'by' => self::getTestSysop()->getUser(),
			'reason' => __METHOD__,
			'expiry' => time() + 100500,
			'sitewide' => $blockIsSitewide,
		] );
		$block->insert();
		if ( $blockedNamespaces !== null ) {
			$restrictions = [];
			foreach ( $blockedNamespaces as $blockedNamespace ) {
				$restrictions[] = new NamespaceRestriction( $block->getId(), $blockedNamespace );
			}
			$block->setRestrictions( $restrictions );
			MediaWikiServices::getInstance()->getBlockRestrictionStore()->insert( $restrictions );
		}
		return $user;
	}

	public function testAllNecessaryFormFieldsArePresent_WhenRenderedInNoScript(): void {
		[ $html ] = $this->executeSpecialPage();

		// FIXME: assert that these are in a <noscript> block
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEMMA_LANGUAGE );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEMMA );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEXICAL_CATEGORY );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEXEME_LANGUAGE );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function testSearchLinkIncludesLemma(): void {
		$request = new FauxRequest( [
			SpecialNewLexeme::FIELD_LEMMA => '">lemma',
		] );

		[ $html ] = $this->executeSpecialPage( '', $request );

		$this->assertStringContainsString( 'search=%22%3Elemma', $html );
	}

	public function testRequestByUserWithoutPermission_accessIsDenied(): void {
		$this->setMwGlobals( [
			'wgGroupPermissions' => [
				'*' => [
					'createpage' => false,
				],
			],
		] );
		$this->resetServices();

		$this->stats->expects( $this->once() )
			->method( 'increment' )
			->with( 'wikibase.lexeme.special.NewLexeme.views' );
		try {
			$this->executeSpecialPage();
			$this->fail();
		} catch ( PermissionsError $exception ) {
			$this->assertSame( 'badaccess-group0', $exception->errors[0][0] );
		}
	}

	public function testInfoPanelEscapesLexemeBoxContents(): void {
		$languageItemId = 'Q10';
		$lexicalCategoryItemId = 'Q11';
		$this->givenItemExists( $languageItemId, '<language>' );
		$this->givenItemExists( $lexicalCategoryItemId, '<lexicalcategory>' );
		$exampleLexemeId = 'L100';
		$exampleLexeme = NewLexeme::havingId( $exampleLexemeId )
			->withLemma( 'en', '<lemma>' )
			->withLanguage( $languageItemId )
			->withLexicalCategory( $lexicalCategoryItemId )
			->build();
		WikibaseRepo::getEntityStore()
			->saveEntity(
				$exampleLexeme,
				'',
				self::getTestUser()->getUser(),
				EDIT_NEW
			);
		$this->editPage(
			Title::makeTitle( NS_MEDIAWIKI, 'Wikibaselexeme-newlexeme-info-panel-example-lexeme-id' ),
			"\n \n \n " . $exampleLexemeId
		);
		$this->setMwGlobals( [
			'wgUseDatabaseMessages' => true,
			'wgLanguageCode' => 'en',
		] );
		$uselang = 'de'; // should fall back to the wgLanguageCode (en) message created above

		[ $html ] = $this->executeSpecialPage( '', null, $uselang );

		// the first three assertions don’t include &gt; because "&lt;language>" is also okay
		$this->assertStringContainsString( '&lt;language', $html );
		$this->assertStringContainsString( '&lt;lexicalcategory', $html );
		$this->assertStringContainsString( '&lt;lemma', $html );
		$this->assertStringNotContainsString( '<language>', $html );
		$this->assertStringNotContainsString( '<lexicalcategory>', $html );
		$this->assertStringNotContainsString( '<lemma>', $html );
	}

	public function testInfoPanelAppliesLanguageFallbacks(): void {
		$languageItemId = 'Q10';
		$languageLabel = 'Test Language';
		$lexicalCategoryItemId = 'Q11';
		$lexicalCategoryLabel = 'Test Lexical Category';
		$lemma = 'Test Lemma';
		$this->givenItemExists( $languageItemId, $languageLabel );
		$this->givenItemExists( $lexicalCategoryItemId, $lexicalCategoryLabel );
		$exampleLexemeId = 'L100';
		$exampleLexeme = NewLexeme::havingId( $exampleLexemeId )
			->withLemma( 'en', $lemma )
			->withLanguage( $languageItemId )
			->withLexicalCategory( $lexicalCategoryItemId )
			->build();
		WikibaseRepo::getEntityStore()
			->saveEntity(
				$exampleLexeme,
				'',
				self::getTestUser()->getUser(),
				EDIT_NEW
			);
		$this->editPage(
			Title::makeTitle( NS_MEDIAWIKI, 'Wikibaselexeme-newlexeme-info-panel-example-lexeme-id/de' ),
			$exampleLexemeId
		);
		$this->setMwGlobals( [
			'wgUseDatabaseMessages' => true,
			'wgLanguageCode' => 'en',
		] );
		$uselang = 'de-at'; // should fall back to the de message created above

		[ $html ] = $this->executeSpecialPage( '', null, $uselang );

		$this->assertStringContainsString( $languageLabel, $html );
		$this->assertStringContainsString( $lexicalCategoryLabel, $html );
		$this->assertStringContainsString( $lemma, $html );
	}

	public function testInfoPanelFallsBackToHardCodedExampleLexeme(): void {
		[ $html ] = $this->executeSpecialPage();

		$this->assertStringContainsString( 'speak', $html );
		$this->assertStringContainsString( 'English', $html );
		$this->assertStringContainsString( 'verb', $html );
	}

	/**
	 * Configure two lexical category item IDs:
	 * Q1 with a German label and an English description,
	 * and Q2 without label or description.
	 * Assert that JSON information about them ends up in the mw.config of the output.
	 */
	public function testLexicalCategorySuggestions(): void {
		$this->setMwGlobals( [
			'wgLexemeLexicalCategoryItemIds' => [ 'Q1', 'Q2' ],
			'wgLanguageCode' => 'de',
		] );
		$labelDescriptionLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$labelDescriptionLookup->expects( $this->exactly( 2 ) )
			->method( 'getLabel' )
			->willReturnCallback( static function ( ItemId $itemId ): ?TermFallback {
				switch ( $itemId->getSerialization() ) {
					case 'Q1':
						return new TermFallback( 'de', 'Nomen', 'de', 'de' );
					case 'Q2':
						return null;
					default:
						throw new Exception( 'Expected Q1 or Q2, got ' . $itemId->getSerialization() );
				}
			} );
		$labelDescriptionLookup->expects( $this->exactly( 2 ) )
			->method( 'getDescription' )
			->willReturnCallback( static function ( ItemId $itemId ): ?TermFallback {
				switch ( $itemId->getSerialization() ) {
					case 'Q1':
						return new TermFallback( 'de', 'lexical category', 'en', 'en' );
					case 'Q2':
						return null;
					default:
						throw new Exception( 'Expected Q1 or Q2, got ' . $itemId->getSerialization() );
				}
			} );
		$labelDescriptionLookupFactory = $this->createMock(
			FallbackLabelDescriptionLookupFactory::class );
		$labelDescriptionLookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->with(
				$this->callback( static function ( Language $language ): bool {
					return $language->getCode() === 'de';
				} ),
				[ new ItemId( 'Q1' ), new ItemId( 'Q2' ) ],
				[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ]
			)
			->willReturn( $labelDescriptionLookup );
		$this->setService( 'WikibaseRepo.FallbackLabelDescriptionLookupFactory',
			$labelDescriptionLookupFactory );

		[ $html ] = $this->executeSpecialPage( '', null, 'de', null, true );

		$expected = [
			[
				'id' => 'Q1',
				'display' => [
					'label' => [
						'language' => 'de',
						'value' => 'Nomen',
					],
					'description' => [
						'language' => 'en', // language fallback
						'value' => 'lexical category',
					],
				],
			],
			[
				'id' => 'Q2',
				'display' => [],
			],
		];

		$this->assertStringContainsString( json_encode( $expected ), $html );
	}

	public function provideUrlParameterTestData(): \Traversable {
		yield 'No url params' => [
			[],
			[],
		];

		yield 'lemma param' => [
			[ SpecialNewLexeme::FIELD_LEMMA => 'foo' ],
			[
				'lemma' => 'foo'
			],
		];

		yield 'spelling variant code param' => [
			[ SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'de' ],
			[
				'spellVarCode' => 'de'
			],
		];

		$lexCatItem = NewItem::withId( 'Q123' );
		$lexCatItem = $lexCatItem->andLabel( 'en', 'lex cat label' );
		yield 'lexical category param' => [
			[
				SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => 'Q123',
			],
			[
				'lexicalCategory' => [
					'display' => [
						'label' => [
							'language' => 'en',
							'value' => 'lex cat label',
						],
					],
					'id' => 'Q123',
				],
			],
			[ $lexCatItem ],
		];

		$languageLabel = 'language label';
		$languageItem = NewItem::withId( 'Q456' );
		$languageItem = $languageItem->andLabel( 'en', $languageLabel );
		$statement = NewStatement::forProperty( 'P123' );
		$statement = $statement->withValue( new StringValue( 'en' ) );
		$languageItem = $languageItem->andStatement( $statement );
		yield 'language param' => [
			[
				SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => 'Q456',
			],
			[
				'language' => [
					'display' => [
						'label' => [
							'language' => 'en',
							'value' => $languageLabel,
						],
					],
					'id' => 'Q456',
					'languageCode' => 'en',
				],
			],
			[ $languageItem ]
		];

		$languageItem = NewItem::withId( 'Q789' );
		$lexCatItem = NewItem::withId( 'Q741' )->andDescription( 'en', 'english description' );
		yield 'language and lexical category' => [
			[
				SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => 'Q741',
				SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => 'Q789',
			],
			[
				'language' => [
					'display' => [],
					'id' => 'Q789',
					'languageCode' => null,
				],
				'lexicalCategory' => [
					'display' => [
						'description' => [
							'language' => 'en',
							'value' => 'english description',
						],
					],
					'id' => 'Q741',
				],
			],
			[ $languageItem, $lexCatItem ],
		];

		$languageItem = NewItem::withId( 'Q852' );
		$statement = NewStatement::someValueFor( 'P123' );
		$languageItem = $languageItem->andStatement( $statement );
		yield 'language code "false" for somevalue snak' => [
			[ SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => 'Q852' ],
			[
				'language' => [
					'display' => [],
					'id' => 'Q852',
					'languageCode' => false,
				],
			],
			[ $languageItem ],
		];
	}

	/**
	 * @dataProvider provideUrlParameterTestData
	 */
	public function testUrlParsing( $urlParams, $expectedConfigValue, $itemsToCreate = [] ): void {
		$this->setMwGlobals( [
			'wgLexemeLanguageCodePropertyId' => 'P123',
		] );

		foreach ( $itemsToCreate as $item ) {
			WikibaseRepo::getEntityStore()
				->saveEntity(
					$item->build(),
					'',
					self::getTestUser()->getUser(),
					EDIT_NEW,
					false
				);
		}

		$request = new FauxRequest( $urlParams );
		[ $html ] = $this->executeSpecialPage( '', $request, 'en', null, true );

		$scriptBlockFound = preg_match( '/<script>.*?<\/script>/s', $html, $scriptHTML );
		$this->assertSame( 1, $scriptBlockFound );

		$expected = [ 'wblSpecialNewLexemeParams' => $expectedConfigValue ];
		$expectedHTML = substr( json_encode( $expected ), 1, -1 );

		$this->assertStringContainsString( $expectedHTML, $scriptHTML[0] );
	}

	public function provideValidEntityCreationRequests(): array {
		return [
			'everything is set' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma text',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
			],
			'using special \'mis\' lemma language' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'mis',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma text',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
			],
		];
	}

	public function provideInvalidEntityCreationRequests() {
		return [
			'unknown language' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'some-weird-language',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'empty lemma' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => '',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lemma too long' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA =>
						str_repeat( 'a', LemmaTermValidator::LEMMA_MAX_LENGTH + 1 ),
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lexical category has wrong format' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => 'x',
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lexeme language has wrong format' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => 'x',
				],
				'(htmlform-invalid-input)',
			],
			'lexical category does not exist' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::NON_EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lexeme language does not exist' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::NON_EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lexeme language is not set' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => '',
				],
				'(htmlform-invalid-input)',
			],
			'lexical category is not set' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => '',
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
		];
	}

	/**
	 * @dataProvider provideValidEntityCreationRequests
	 */
	public function testEntityIsBeingCreated_WhenValidInputIsGiven( array $formData ) {
		$this->stats->expects( $this->exactly( 2 ) )
			->method( 'increment' )
			->withConsecutive(
				[ 'wikibase.lexeme.special.NewLexeme.views' ],
				[ 'wikibase.lexeme.special.NewLexeme.nojs.create' ]
			);
		parent::testEntityIsBeingCreated_WhenValidInputIsGiven( $formData );
	}

	/**
	 * @param string $url
	 */
	protected function extractEntityIdFromUrl( $url ): LexemeId {
		$serialization = preg_replace( '@^.*(L\d+)$@', '$1', $url );

		return new LexemeId( $serialization );
	}

	protected function assertEntityMatchesFormData( array $form, EntityDocument $entity ): void {
		$this->assertInstanceOf( Lexeme::class, $entity );
		/** @var Lexeme $entity */

		$language = $form[SpecialNewLexeme::FIELD_LEMMA_LANGUAGE];
		self::assertEquals(
			$form[SpecialNewLexeme::FIELD_LEMMA],
			$entity->getLemmas()->getByLanguage( $language )->getText()
		);

		if ( $form[SpecialNewLexeme::FIELD_LEXICAL_CATEGORY] ) {
			self::assertEquals(
				$form[SpecialNewLexeme::FIELD_LEXICAL_CATEGORY],
				$entity->getLexicalCategory()->getSerialization()
			);
		}

		if ( $form[SpecialNewLexeme::FIELD_LEXEME_LANGUAGE] ) {
			self::assertEquals(
				$form[SpecialNewLexeme::FIELD_LEXEME_LANGUAGE],
				$entity->getLanguage()->getSerialization()
			);
		}
	}

	private function givenItemExists( string $id, ?string $enLabel = null ): void {
		$item = NewItem::withId( $id );
		if ( $enLabel !== null ) {
			$item = $item->andLabel( 'en', $enLabel );
		}

		WikibaseRepo::getEntityStore()
			->saveEntity(
				$item->build(),
				'',
				self::getTestUser()->getUser(),
				EDIT_NEW,
				false
			);
	}

}
