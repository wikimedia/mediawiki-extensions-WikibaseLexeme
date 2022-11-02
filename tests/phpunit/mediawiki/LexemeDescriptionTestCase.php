<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\Tests\FakeCache;

/**
 * Trait for producing mockups for tests dealing with Lemma descriptions
 *
 * @license GPL-2.0-or-later
 */
trait LexemeDescriptionTestCase {

	private function getMockLabel( $id, $language ) {
		if ( !isset( $this->labels[$id] ) ) {
			throw new TermLookupException( $id, $language );
		}
		if ( !isset( $this->labels[$id][$language] ) ) {
			return null;
		}
		return $this->labels[$id][$language];
	}

	/**
	 * @return TermLookup
	 */
	private function getMockTermLookup() {
		$lookup = $this->createMock( TermLookup::class );
		$lookup->method( 'getLabel' )->willReturnCallback( function ( EntityId $id, $language ) {
			return $this->getMockLabel( $id->getSerialization(), $language );
		} );
		$lookup->method( 'getLabels' )->willReturnCallback( function ( EntityId $id, array $languages ) {
			$result = [];
			foreach ( $languages as $language ) {
				$result[$language] = $this->getMockLabel( $id->getSerialization(), $language );
			}
			return $result;
		} );
		return $lookup;
	}

	/**
	 * @param string[] $lookupIds
	 * @return TermBuffer
	 */
	private function getMockTermBuffer( $lookupIds, $languages ) {
		$fetchIds = array_combine( $lookupIds, array_map( static function ( $id ) {
			return new ItemId( $id );
		}, $lookupIds ) );

		$lookup = $this->createMock( TermBuffer::class );
		if ( empty( $lookupIds ) ) {
			$lookup->expects( $this->never() )
				->method( 'prefetchTerms' );
			return $lookup;
		}
		$lookup->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with( $fetchIds, [ 'label' ], $languages )
			->willReturn( true );
		return $lookup;
	}

	/**
	 * @return EntityIdParser
	 */
	private function getIdParser() {
		return new DispatchingEntityIdParser(
			[
				LexemeId::PATTERN => static function ( $s ) {
					return new LexemeId( $s );
				},
				FormId::PATTERN => static function ( $s ) {
					return new FormId( $s );
				},
				ItemId::PATTERN => static function ( $s ) {
					return new ItemId( $s );
				},
			]
		);
	}

	private function getFakeRedirectResolvingLatestRevisionLookup() {
		$lookup = $this->createMock( RedirectResolvingLatestRevisionLookup::class );
		$lookup->method( 'lookupLatestRevisionResolvingRedirect' )->willReturnCallback(
			static function ( EntityId $entityId ) {
				return [ 0, $entityId ];
			}
		);

		return $lookup;
	}

	/**
	 * @param string[] $fetchIds IDs we expect to be fetched
	 * @param string $displayLanguage Display language to use
	 * @return FallbackLabelDescriptionLookupFactory
	 */
	private function getTermLookupFactory( $fetchIds, $displayLanguage ) {
		$langFactory = new LanguageFallbackChainFactory();

		return new FallbackLabelDescriptionLookupFactory(
			$langFactory,
			$this->getFakeRedirectResolvingLatestRevisionLookup(),
			new TermFallbackCacheFacade(
				new FakeCache(),
				10
			),
			$this->getMockTermLookup(),
			$this->getMockTermBuffer( $fetchIds,
				$langFactory->newFromLanguageCode( $displayLanguage )->getFetchLanguageCodes() )
		);
	}

}
