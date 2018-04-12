'use strict';

const MWBot = require( 'mwbot' ),
	bot = new MWBot( {
		apiUrl: browser.options.baseUrl + '/api.php'
	} );

class LexemeApi {

	static getTestString() {
		return Math.random().toString() + '-öäü-♠♣♥♦';
	}

	/**
	 * Create a lexeme
	 *
	 * @param {object} lexeme Optional lexeme definition to merge into default definition
	 * @return {Promise}
	 */
	create( lexeme ) {
		lexeme = Object.assign( {
			lemmas: {
				en: {
					value: this.constructor.getTestString(),
					language: 'en'
				}
			},
			lexicalCategory: null, // if null a new lexicalCategory is created and used for the lexeme
			language: null // if null a new language is created and used for the lexeme
		}, lexeme );

		return bot.getEditToken()
			.then( () => {
				return new Promise( ( resolve, reject ) => {
					if ( lexeme.lexicalCategory !== null ) { // optionally skip creation
						resolve();
					}

					bot.request( {
						action: 'wbeditentity',
						'new': 'item',
						data: JSON.stringify( {} ),
						token: bot.editToken
					} ).then( ( payload ) => {
						lexeme.lexicalCategory = payload.entity.id;

						resolve();
					}, reject );
				} );
			} ).then( () => {
				return new Promise( ( resolve, reject ) => {
					if ( lexeme.language !== null ) { // optionally skip creation
						resolve();
					}

					bot.request( {
						action: 'wbeditentity',
						'new': 'item',
						data: JSON.stringify( {} ),
						token: bot.editToken
					} ).then( ( payload ) => {
						lexeme.language = payload.entity.id;

						resolve();
					}, reject );
				} );
			} ).then( () => {
				return new Promise( ( resolve, reject ) => {
					bot.request( {
						action: 'wbeditentity',
						'new': 'lexeme',
						data: JSON.stringify( lexeme ),
						token: bot.editToken
					} ).then( ( payload ) => {
						resolve( payload.entity );
					}, reject );
				} );
			} );
	}

	/**
	 * Get information about a lexeme
	 *
	 * @param {string} lexemeId
	 * @return {Promise}
	 */
	get( lexemeId ) {
		return new Promise( ( resolve, reject ) => {
			bot.request( {
				action: 'wbgetentities',
				ids: lexemeId
			} ).then( ( response ) => {
				resolve( response.entities[ lexemeId ] );
			}, reject );
		} );
	}

	/**
	 * Add a new form to a lexeme
	 *
	 * @param {string} lexemeId
	 * @param {object} form
	 * @return {Promise}
	 */
	addForm( lexemeId, form ) {
		return bot.request( {
			action: 'wbladdform',
			lexemeId: lexemeId,
			data: JSON.stringify( form ),
			token: bot.editToken
		} );
	}

}

module.exports = new LexemeApi();
