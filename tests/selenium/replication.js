'use strict';

class Replication {
	// Wait for 120% of the current replication lag to increase the chance we
	// get up to date entity data, even if reading from a replica.
	waitForReplicationLag( botPromise ) {
		browser.call( () => this.getReplicationLag( botPromise )
			.then( ( replag ) => {
				browser.log( `Waiting ${replag * 1200} ms for replication lag of ${replag} s` );
				browser.pause( replag * 1200 );
			} )
		);
	}

	/**
	 * Get the current replication lag.
	 *
	 * @param {Promise} botPromise resolving to MWBot
	 *
	 * @return {Promise}
	 */
	getReplicationLag( botPromise ) {
		return botPromise.then( ( bot ) => bot.request( {
			action: 'query',
			meta: 'siteinfo',
			siprop: 'dbrepllag'
		} ) ).then( ( response ) => {
			return Math.max( 0, response.query.dbrepllag[ 0 ].lag );
		} );
	}

}

module.exports = new Replication();
