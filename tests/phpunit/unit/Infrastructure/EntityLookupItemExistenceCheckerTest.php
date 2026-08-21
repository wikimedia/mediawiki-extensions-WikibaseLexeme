<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Infrastructure;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\Lexeme\Infrastructure\EntityLookupItemExistenceChecker;

/**
 * @covers \Wikibase\Lexeme\Infrastructure\EntityLookupItemExistenceChecker
 *
 * @license GPL-2.0-or-later
 */
class EntityLookupItemExistenceCheckerTest extends MediaWikiUnitTestCase {

	public function testGivenExistingItem_exists(): void {
		$itemId = new ItemId( 'Q123' );

		$this->assertTrue(
			$this->newChecker( new Item( $itemId ) )->exists( $itemId )
		);
	}

	public function testGivenNonexistentItem_doesNotExist(): void {
		$this->assertFalse(
			$this->newChecker()->exists( new ItemId( 'Q999' ) )
		);
	}

	private function newChecker( Item ...$items ): EntityLookupItemExistenceChecker {
		$entityLookup = new InMemoryEntityLookup();
		foreach ( $items as $item ) {
			$entityLookup->addEntity( $item );
		}

		return new EntityLookupItemExistenceChecker( $entityLookup );
	}

}
