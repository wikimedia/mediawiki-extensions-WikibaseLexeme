'use strict';

const LexemeApi = require( './lexeme.api' );

class Replication {
	// Wait for 120% of the current replication lag to increase the chance we
	// get up to date entity data below, even if reading from a replica.
	waitForReplicationLag() {
		browser.call( () => LexemeApi.getReplicationLag()
			.then( ( replag ) => {
				browser.log( `Waiting ${replag * 1200} ms for replication lag of ${replag} s` );
				browser.pause( replag * 1200 );
			} )
		);
	}

}

module.exports = new Replication();
