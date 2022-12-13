<?php

declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\Store\EntityLookupLemmaLookup;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\EntityLookupLemmaLookup
 *
 * @license GPL-2.0-or-later
 */
final class EntityLemmaLookupTest extends TestCase {

	public function test_returns_empty_TermList_on_UnresolvedEntityRedirectException(): void {
		$fakeEntityLookup = $this->createMock( EntityLookup::class );
		$fakeEntityLookup->method( 'getEntity' )->willThrowException(
			$this->createStub( UnresolvedEntityRedirectException::class )
		);
		$lexemeId = new LexemeId( 'L1' );
		$lemmaLookup = new EntityLookupLemmaLookup( $fakeEntityLookup );

		$actualResult = $lemmaLookup->getLemmas( $lexemeId );

		$this->assertTrue( $actualResult->isEmpty() );
	}

	public function test_returns_empty_TermList_on_missing_Lexeme(): void {
		$fakeEntityLookup = $this->createMock( EntityLookup::class );
		$fakeEntityLookup->method( 'getEntity' )->willReturn( null );
		$lexemeId = new LexemeId( 'L1' );
		$lemmaLookup = new EntityLookupLemmaLookup( $fakeEntityLookup );

		$actualResult = $lemmaLookup->getLemmas( $lexemeId );

		$this->assertTrue( $actualResult->isEmpty() );
	}

	public function test_returns_Lemmas_from_Lexeme(): void {
		$lexemeId = new LexemeId( 'L1' );
		$lemmas = new TermList( [ new Term( 'en', 'color' ), new Term( 'en-gb', 'colour' ) ] );
		$lexeme = new Lexeme( $lexemeId, $lemmas );
		$fakeEntityLookup = $this->createMock( EntityLookup::class );
		$fakeEntityLookup->method( 'getEntity' )->willReturn( $lexeme );
		$lemmaLookup = new EntityLookupLemmaLookup( $fakeEntityLookup );

		$actualResult = $lemmaLookup->getLemmas( $lexemeId );

		$this->assertSame( $lemmas, $actualResult );
	}

}
