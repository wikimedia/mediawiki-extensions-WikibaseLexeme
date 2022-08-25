<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiMain;
use ApiUsageException;
use ChangeTags;
use MediaWiki\MediaWikiServices;
use RequestContext;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\MediaWiki\Api\MergeLexemes;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\MergeLexemes
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class MergeLexemesTest extends WikibaseLexemeApiTestCase {

	private const API_ACTION = 'wblmergelexemes';

	private const DEFAULT_LANGUAGE = 'Q1';

	private const DEFAULT_LEXICAL_CATEGORY = 'Q2';

	public function testGivenSuccessfulMerge_respondsWithSuccessMessage() {
		$source = NewLexeme::havingId( 'L1' )
			->withLexicalCategory( self::DEFAULT_LEXICAL_CATEGORY )
			->withLanguage( self::DEFAULT_LANGUAGE )
			->withLemma( 'en', 'color' )
			->build();
		$target = NewLexeme::havingId( 'L2' )
			->withLexicalCategory( self::DEFAULT_LEXICAL_CATEGORY )
			->withLanguage( self::DEFAULT_LANGUAGE )
			->withLemma( 'en-gb', 'colour' )
			->build();

		$this->saveLexemes( $source, $target );

		$response = $this->executeApiWithIds(
			$source->getId()->getSerialization(),
			$target->getId()->getSerialization()
		);

		$this->assertSame( 1, $response['success'] );

		/** @var Lexeme $postMergeTarget */
		$postMergeTarget = WikibaseRepo::getEntityLookup()
			->getEntity( $target->getId() );

		$this->assertEquals(
			$source->getLemmas()->getByLanguage( 'en' ),
			$postMergeTarget->getLemmas()->getByLanguage( 'en' )
		);
		$this->assertEquals(
			$target->getLemmas()->getByLanguage( 'en-gb' ),
			$postMergeTarget->getLemmas()->getByLanguage( 'en-gb' )
		);
	}

	public function testCustomSummaryUse() {
		$source = $this->newLexeme( 'L123' )
			->withLemma( 'en-gb', 'foo' )
			->build();
		$target = $this->newLexeme( 'L321' )->build();
		$this->saveLexemes( $source, $target );

		$summary = 'some fancy summary';
		$this->executeApiWithIds(
			$source->getId()->getSerialization(),
			$target->getId()->getSerialization(),
			$summary
		);

		$revId = WikibaseRepo::getStore()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED )
			->getLatestRevisionId( $target->getId() )->onConcreteRevision( static function ( $id ) {
				return $id;
			} )->onNonexistentEntity( function () {
				$this->fail( 'Target entity went away!?' );
			} )->onRedirect( function () {
				$this->fail( 'Target entity was redirected!?' );
			} )->map();
		$revision = MediaWikiServices::getInstance()->getRevisionStore()
			->getRevisionById( $revId );

		$this->assertStringContainsString( $summary, $revision->getComment()->text );
	}

	public function testRequestByUserWithoutPermission_accessIsDenied() {
		$this->setMwGlobals( [
			'wgGroupPermissions' => [
				'*' => [
					'read' => true,
					'lexeme-merge' => false
				]
			]
		] );
		$this->resetServices();

		try {
			$this->executeApiWithIds( 'L123', 'L321' );
			$this->fail( 'User without merge permissions should not be able to merge' );
		} catch ( ApiUsageException $e ) {
			$this->assertEquals(
				'writeapidenied',
				$e->getMessageObject()->getApiCode()
			);
		}
	}

	/**
	 * @dataProvider invalidParamsProvider
	 */
	public function testGivenInvalidInput_errorIsReported( $source, $target ) {
		try {
			$this->executeApiWithIds( $source, $target );
		} catch ( ApiUsageException $exception ) {
			$this->assertEquals(
				'invalid-entity-id',
				$exception->getMessageObject()->getApiCode()
			);
		}
	}

	public function invalidParamsProvider() {
		yield 'invalid source' => [ 'Q7', 'L123' ];
		yield 'invalid target' => [ 'L321', 'potato' ];
	}

	public function testGivenSameLexemeId_errorIsReported() {
		$this->saveLexemes( $this->newLexeme( 'L123' )->build() );

		try {
			$this->executeApiWithIds( 'L123', 'L123' );
		} catch ( ApiUsageException $exception ) {
			$this->assertEquals(
				'cant-merge-self',
				$exception->getMessageObject()->getApiCode()
			);
		}
	}

	public function testNeedsCsrfToken() {
		$this->assertEquals(
			'csrf',
			$this->newMergeLexemes()->needsToken()
		);
	}

	public function testMergesLexemesWithTags() {
		$source = NewLexeme::havingId( 'L1' )
			->withLexicalCategory( self::DEFAULT_LEXICAL_CATEGORY )
			->withLanguage( self::DEFAULT_LANGUAGE )
			->withLemma( 'en', 'color' )
			->build();
		$target = NewLexeme::havingId( 'L2' )
			->withLexicalCategory( self::DEFAULT_LEXICAL_CATEGORY )
			->withLanguage( self::DEFAULT_LANGUAGE )
			->withLemma( 'en-gb', 'colour' )
			->build();

		$this->saveLexemes( $source, $target );
		$dummyTag = __METHOD__ . '-dummy-tag';
		ChangeTags::defineTag( $dummyTag );

		$params = [
			'action' => self::API_ACTION,
			MergeLexemes::SOURCE_ID_PARAM => $source->getId(),
			MergeLexemes::TARGET_ID_PARAM => $target->getId(),
			'tags' => $dummyTag
		];

		$shouldNotBeCalled = function () {
			$this->fail( 'Should not be called' );
		};

		$this->doApiRequestWithToken( $params );
		$lastRevIdResult = WikibaseRepo::getEntityRevisionLookup()->getLatestRevisionId(
			$target->getId(),
			LookupConstants::LATEST_FROM_MASTER
		)->onConcreteRevision( static function ( $revisionId )  {
			return $revisionId;
		} )
		->onRedirect( $shouldNotBeCalled )
		->onNonexistentEntity( $shouldNotBeCalled )
		->map();

		$this->assertContains( $dummyTag, ChangeTags::getTags( $this->db, null, $lastRevIdResult ) );
	}

	private function executeApiWithIds( $sourceId, $targetId, $summary = null ) {
		$params = [
			'action' => self::API_ACTION,
			MergeLexemes::SOURCE_ID_PARAM => $sourceId,
			MergeLexemes::TARGET_ID_PARAM => $targetId,
		];

		if ( $summary ) {
			$params[MergeLexemes::SUMMARY_PARAM] = $summary;
		}

		list( $response ) = $this->doApiRequestWithToken( $params );

		return $response;
	}

	private function saveLexemes( ...$lexemes ) {
		$language = NewItem::withId( self::DEFAULT_LANGUAGE )->build();
		$lexCat = NewItem::withId( self::DEFAULT_LEXICAL_CATEGORY )->build();

		$this->saveEntity( $language );
		$this->saveEntity( $lexCat );

		foreach ( $lexemes as $lexeme ) {
			$this->saveEntity( $lexeme );
		}
	}

	private function newLexeme( $id ): NewLexeme {
		return NewLexeme::havingId( $id )
			->withLanguage( self::DEFAULT_LANGUAGE )
			->withLexicalCategory( self::DEFAULT_LEXICAL_CATEGORY )
			->withLemma( 'en', 'potato' );
	}

	/**
	 * for unit test use
	 */
	private function newMergeLexemes(): MergeLexemes {
		$mainModule = $this->createMock( ApiMain::class );
		$mainModule->method( 'getContext' )
			->willReturn( $this->createMock( RequestContext::class ) );

		return new MergeLexemes(
			$mainModule,
			self::API_ACTION,
			function () {
				return $this->createMock( ApiErrorReporter::class );
			}
		);
	}

}
