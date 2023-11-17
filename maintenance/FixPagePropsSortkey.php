<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Maintenance;

use LoggedUpdateMaintenance;

/**
 * Maintenance script to fix the pp_sortkey column of the page_props table
 * for pp_propname wb-claims, wbl-forms and wbl-senses.
 *
 * Due to a bug (T350224), some lexemes had their pp_sortkey set to NULL,
 * rather than the number of statements, forms or senses.
 * To fix this, set the pp_sortkey based on the (assumed correct) pp_value.
 * Note that we do not limit this to the Lexeme namespace (no JOIN with the page table):
 * the pp_sortkey is not expected to be NULL anywhere else,
 * but even if it is, setting it to the numerical value of the pp_value should be correct.
 * (But we only do this for the three pp_propname strings known to be affected,
 * not any random other page prop.)
 *
 * Usually, this script is run via update.php and there is no need to run it manually.
 *
 * This maintenance script can be removed again after a while
 * (once we assume all installations have run this script to fix their old data).
 * The bug was first introduced in the REL1_39 branch (MediaWiki 1.39).
 *
 * @license GPL-2.0-or-later
 */
class FixPagePropsSortkey extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			'Fix the pp_sortkey of wb-claims, wbl-forms and wbl-senses page props ' .
			'where it is NULL (see T350224).'
		);
		$this->requireExtension( 'WikibaseLexeme' );
		$this->requireExtension( 'WikibaseRepository' );
	}

	public function doDBUpdates(): bool {
		$dbr = $this->getDB( DB_REPLICA );
		$dbw = $this->getDB( DB_PRIMARY );

		$pageIdsQueryBuilder = $dbr->newSelectQueryBuilder()
			->select( 'pp_page' )
			->from( 'page_props' )
			->where( [
				'pp_propname' => [ 'wb-claims', 'wbl-forms', 'wbl-senses' ],
				'pp_sortkey' => null,
			] )
			->orderBy( [ 'pp_propname', 'pp_page' ] ) // uses pp_propname_sortkey_page index
			->limit( $this->getBatchSize() )
			->caller( __METHOD__ );
		$updateQueryBuilder = $dbw->newUpdateQueryBuilder()
			->update( 'page_props' )
			->set( 'pp_sortkey = CAST(pp_value AS FLOAT)' )
			->where( [
				'pp_propname' => [ 'wb-claims', 'wbl-forms', 'wbl-senses' ],
				'pp_sortkey' => null,
				// pp_page condition added in loop, per iteration
			] )
			->caller( __METHOD__ );

		$this->output( __CLASS__ . ' running...' . PHP_EOL );

		while ( true ) {
			$pageIds = ( clone $pageIdsQueryBuilder )->fetchFieldValues();
			if ( !$pageIds ) {
				break;
			}
			$pageIds = array_values( array_unique( $pageIds ) );

			$this->beginTransaction( $dbw, __METHOD__ );
			( clone $updateQueryBuilder )
				->andWhere( [ 'pp_page' => $pageIds ] )
				->execute();
			$this->commitTransaction( $dbw, __METHOD__ ); // this also waits for replication
			$minPageId = reset( $pageIds );
			$maxPageId = end( $pageIds );
			// note: because we order by pp_propname first, it’s theoretically possible that
			// $minPageId > $maxPageId; however, the UPDATE affects all page props at once,
			// so in practice the script will most likely only make one pass through the table
			$this->output( "Update page IDs from $minPageId to $maxPageId..." . PHP_EOL );
		}

		$this->output( 'Done.' . PHP_EOL );

		return true;
	}

	protected function getUpdateKey(): string {
		return __CLASS__;
	}

}

return FixPagePropsSortkey::class;
