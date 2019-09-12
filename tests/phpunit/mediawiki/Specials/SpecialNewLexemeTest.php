<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use FauxRequest;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\Restriction\NamespaceRestriction;
use MediaWiki\MediaWikiServices;
use PermissionsError;
use PHPUnit\Framework\MockObject\MockObject;
use RequestContext;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Specials\SpecialNewLexeme;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\Specials\SpecialNewEntityTestCase;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SummaryFormatter;

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

	const EXISTING_ITEM_ID = 'Q1';
	const NON_EXISTING_ITEM_ID = 'Q100';

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'page';
		$this->givenItemExists( self::EXISTING_ITEM_ID );
	}

	/**
	 * @return SummaryFormatter|MockObject
	 */
	private function getMockSummaryFormatter() {
		$summaryFormatter = $this->getMockBuilder( SummaryFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$summaryFormatter->method( 'formatSummary' )
			->willReturnCallback( function ( FormatableSummary $summary ) {
				return 'MOCKFORMAT: ' .
					$summary->getMessageKey() .
					' ' .
					$summary->getUserSummary();
			} );
		return $summaryFormatter;
	}

	protected function newSpecialPage() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$summaryFormatter = $this->getMockSummaryFormatter();

		return new SpecialNewLexeme(
			$this->copyrightView,
			new EntityNamespaceLookup( [ Lexeme::ENTITY_TYPE => 146 ] ),
			$summaryFormatter,
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory()
		);
	}

	public function testRateLimitIsCheckedWhenEditing() {
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

		list( $html, ) = $this->executeSpecialPage( '', $request, 'qqx' );

		$this->assertContains( '(actionthrottledtext)', $html );
	}

	/**
	 * @throws \Exception
	 */
	public function testExceptionWhenUserBlockedOnNamespace() {
		$user = $this->getTestBlockedUser( false, [ 146 ] );

		$this->setExpectedException( \UserBlockedError::class );
		$this->executeSpecialPage( '', null, null, $user );
	}

	public function testNoExceptionWhenUserBlockedOnDifferentNamespace() {
		$user = $this->getTestBlockedUser( false, [ NS_MAIN ] );

		// to avoid test being tagged as risky for not making assertions
		$this->addToAssertionCount( 1 );
		$this->executeSpecialPage( '', null, null, $user );
	}

	/**
	 * @throws \Exception
	 */
	public function testExceptionWhenUserBlockedSitewide() {
		$user = $this->getTestBlockedUser( true );

		$this->setExpectedException( \UserBlockedError::class );
		$this->executeSpecialPage( '', null, null, $user );
	}

	private function getTestBlockedUser( $blockIsSitewide, $blockedNamespaces = null ) {
		$user = $this->getMutableTestUser()->getUser();
		$block = new DatabaseBlock( [
			'address' => $user->getName(),
			'user' => $user->getID(),
			'by' => $this->getTestSysop()->getUser()->getId(),
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

	public function testAllNecessaryFormFieldsArePresent_WhenRendered() {
		list( $html ) = $this->executeSpecialPage();

		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEMMA_LANGUAGE );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEMMA );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEXICAL_CATEGORY );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewLexeme::FIELD_LEXEME_LANGUAGE );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function testRequestByUserWithoutPermission_accessIsDenied() {
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

	public function provideValidEntityCreationRequests() {
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
				'language code was not recognized',
			],
			'empty lemma' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => '',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'value is required',
			],
			'lexical category has wrong format' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => 'x',
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'invalid format',
			],
			'lexeme language has wrong format' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => 'x',
				],
				'invalid format',
			],
			'lexical category does not exist' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::NON_EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'does not exist',
			],
			'lexeme language does not exist' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::NON_EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'does not exist',
			],
			'lexeme language is not set' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => self::EXISTING_ITEM_ID,
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => '',
				],
				'invalid format',
			],
			'lexical category is not set' => [
				[
					SpecialNewLexeme::FIELD_LEMMA_LANGUAGE => 'en',
					SpecialNewLexeme::FIELD_LEMMA => 'some lemma',
					SpecialNewLexeme::FIELD_LEXICAL_CATEGORY => '',
					SpecialNewLexeme::FIELD_LEXEME_LANGUAGE => self::EXISTING_ITEM_ID,
				],
				'invalid format',
			],
		];
	}

	/**
	 * @param string $url
	 *
	 * @return LexemeId
	 */
	protected function extractEntityIdFromUrl( $url ) {
		$serialization = preg_replace( '@^.*(L\d+)$@', '$1', $url );

		return new LexemeId( $serialization );
	}

	protected function assertEntityMatchesFormData( array $form, EntityDocument $entity ) {
		$this->assertInstanceOf( Lexeme::class, $entity );
		/** @var Lexeme $entity */

		$language = $form[ SpecialNewLexeme::FIELD_LEMMA_LANGUAGE ];
		self::assertEquals(
			$form[ SpecialNewLexeme::FIELD_LEMMA ],
			$entity->getLemmas()->getByLanguage( $language )->getText()
		);

		if ( $form[ SpecialNewLexeme::FIELD_LEXICAL_CATEGORY ] ) {
			self::assertEquals(
				$form[ SpecialNewLexeme::FIELD_LEXICAL_CATEGORY ],
				$entity->getLexicalCategory()->getSerialization()
			);
		}

		if ( $form[ SpecialNewLexeme::FIELD_LEXEME_LANGUAGE ] ) {
			self::assertEquals(
				$form[ SpecialNewLexeme::FIELD_LEXEME_LANGUAGE ],
				$entity->getLanguage()->getSerialization()
			);
		}
	}

	/**
	 * @param string $id
	 */
	private function givenItemExists( $id ) {
		WikibaseRepo::getDefaultInstance()
			->getEntityStore()
			->saveEntity(
				NewItem::withId( $id )->build(),
				'',
				$this->getTestUser()->getUser(),
				EDIT_NEW,
				false
			);
	}

}
