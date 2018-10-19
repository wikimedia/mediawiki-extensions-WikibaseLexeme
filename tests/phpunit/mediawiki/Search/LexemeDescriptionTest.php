<?php
namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lexeme\Domain\DataModel\FormId;
use Wikibase\Lexeme\Domain\DataModel\LexemeId;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * Trait for producing mockups for tests dealing with Lemma descriptions
 */
trait LexemeDescriptionTest {

	public function setUp() {
		parent::setUp();
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}
	}

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
		$lookup = $this->getMockBuilder( TermLookup::class )->disableOriginalConstructor()->getMock();
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
		$fetchIds = array_combine( $lookupIds, array_map( function ( $id ) {
			return new ItemId( $id );
		}, $lookupIds ) );

		$lookup = $this->getMockBuilder( TermBuffer::class )->disableOriginalConstructor()->getMock();
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
				LexemeId::PATTERN => function ( $s ) {
					return new LexemeId( $s );
				},
				FormId::PATTERN => function ( $s ) {
					return new FormId( $s );
				},
				ItemId::PATTERN => function ( $s ) {
					return new ItemId( $s );
				},
			]
		);
	}

	/**
	 * @param string[] $fetchIds IDs we expect to be fetched
	 * @param string $displayLanguage Display language to use
	 * @return LanguageFallbackLabelDescriptionLookupFactory
	 */
	private function getTermLookupFactory( $fetchIds, $displayLanguage ) {
		$langFactory = new LanguageFallbackChainFactory();

		return new LanguageFallbackLabelDescriptionLookupFactory(
			$langFactory,
			$this->getMockTermLookup(),
			$this->getMockTermBuffer( $fetchIds,
				$langFactory->newFromLanguageCode( $displayLanguage )->getFetchLanguageCodes() )
		);
	}

}
