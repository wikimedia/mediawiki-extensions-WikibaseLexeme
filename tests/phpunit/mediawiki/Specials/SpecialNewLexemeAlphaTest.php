<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use FauxRequest;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\Restriction\NamespaceRestriction;
use MediaWiki\MediaWikiServices;
use PermissionsError;
use RequestContext;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Specials\SpecialNewLexemeAlpha;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\Specials\SpecialNewEntityTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Specials\SpecialNewLexemeAlpha
 *
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class SpecialNewLexemeAlphaTest extends SpecialNewEntityTestCase {

	private const EXISTING_ITEM_ID = 'Q1';
	private const NON_EXISTING_ITEM_ID = 'Q100';

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );

		$this->tablesUsed[] = 'page';
		$this->givenItemExists( self::EXISTING_ITEM_ID );
	}

	private function getMockSummaryFormatter(): SummaryFormatter {
		$summaryFormatter = $this->getMockBuilder( SummaryFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$summaryFormatter->method( 'formatSummary' )
			->willReturnCallback( static function ( FormatableSummary $summary ) {
				return 'MOCKFORMAT: ' .
					$summary->getMessageKey() .
					' ' .
					$summary->getUserSummary();
			} );
		return $summaryFormatter;
	}

	protected function newSpecialPage(): SpecialNewLexemeAlpha {
		$summaryFormatter = $this->getMockSummaryFormatter();

		return new SpecialNewLexemeAlpha(
			self::TAGS,
			WikibaseRepo::getEditEntityFactory(),
			new EntityNamespaceLookup( [ Lexeme::ENTITY_TYPE => 146 ] ),
			WikibaseRepo::getEntityTitleStoreLookup(),
			$summaryFormatter
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
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexemeAlpha::FIELD_LEMMA );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function testRequestByUserWithoutPermission_accessIsDenied(): void {
		$this->setMwGlobals( [
			'wgGroupPermissions' => [
				'*' => [
					'createpage' => false
				]
			]
		] );
		$this->resetServices();

		try {
			$this->executeSpecialPage();
			$this->fail();
		} catch ( PermissionsError $exception ) {
			$this->assertSame( 'badaccess-group0', $exception->errors[0][0] );
		}
	}

	public function provideValidEntityCreationRequests(): array {
		return [
			'everything is set' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma text',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
			],
			'using special \'mis\' lemma language' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'mis',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma text',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
			],
		];
	}

	public function provideInvalidEntityCreationRequests() {
		return [
			'unknown language' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'some-weird-language',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'empty lemma' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => '',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lemma too long' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => str_repeat( 'a', 1000 + 1 ),
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lexical category has wrong format' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => 'x',
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lexeme language has wrong format' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => 'x',
				],
				'(htmlform-invalid-input)',
			],
			'lexical category does not exist' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::NON_EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lexeme language does not exist' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::NON_EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
			'lexeme language is not set' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => '',
				],
				'(htmlform-invalid-input)',
			],
			'lexical category is not set' => [
				[
					SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexemeAlpha::FIELD_LEMMA => 'some lemma',
					SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY => '',
					SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'(htmlform-invalid-input)',
			],
		];
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

		$language = $form[ SpecialNewLexemeAlpha::FIELD_LEMMA_LANGUAGE ];
		self::assertEquals(
			$form[ SpecialNewLexemeAlpha::FIELD_LEMMA ],
			$entity->getLemmas()->getByLanguage( $language )->getText()
		);

		if ( $form[ SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY ] ) {
			self::assertEquals(
				$form[ SpecialNewLexemeAlpha::FIELD_LEXICAL_CATEGORY ],
				$entity->getLexicalCategory()->getSerialization()
			);
		}

		if ( $form[ SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE ] ) {
			self::assertEquals(
				$form[ SpecialNewLexemeAlpha::FIELD_LEXEME_LANGUAGE ],
				$entity->getLanguage()->getSerialization()
			);
		}
	}

	private function givenItemExists( string $id ): void {
		WikibaseRepo::getEntityStore()
			->saveEntity(
				NewItem::withId( $id )->build(),
				'',
				self::getTestUser()->getUser(),
				EDIT_NEW,
				false
			);
	}

}
