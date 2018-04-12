'use strict';

const MWBot = require( 'mwbot' ),
	bot = new MWBot( {
		apiUrl: browser.options.baseUrl + '/api.php'
	} );

// TODO: this class should probably go to the "main" Wikibase repository
class WikibaseApi {

	/**
	 * Create an item
	 *
	 * @param {string} label Optional English label of the item
	 * @return {Promise}
	 */
	createItem( label ) {
		let itemData = {};
		if ( label ) {
			itemData = {
				labels: {
					en: {
						language: 'en',
						value: label
					}
				}
			};
		}

		return bot.getEditToken()
			.then( () => {
				return new Promise( ( resolve, reject ) => {
					bot.request( {
						action: 'wbeditentity',
						'new': 'item',
						data: JSON.stringify( itemData ),
						token: bot.editToken
					} ).then( ( response ) => {
						resolve( response.entity.id );
					}, reject );
				} );
			} );
	}

}

module.exports = new WikibaseApi();
