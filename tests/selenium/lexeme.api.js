'use strict';

const url = require( 'url' ), // https://nodejs.org/docs/latest/api/url.html
	baseUrl = url.parse( browser.options.baseUrl ), // http://webdriver.io/guide/testrunner/browserobject.html
	Bot = require( 'nodemw' ), // https://github.com/macbre/nodemw
	client = new Bot( {
		protocol: baseUrl.protocol,
		server: baseUrl.hostname,
		port: baseUrl.port,
		path: baseUrl.path,
		username: browser.options.username,
		password: browser.options.password
	} ),
	METHOD_POST = 'POST';

class LexemeApi {

	static getTestString() {
		return Math.random().toString() + '-öäü-♠♣♥♦';
	}

	loginAndGetToken() {
		return new Promise( ( resolve, reject ) => {
			client.logIn( ( err ) => {
				if ( err ) {
					return reject( err );
				}
				resolve();
			} );
		} ).then( () => {
			return new Promise( ( resolve, reject ) => {
				client.getToken( '', '', ( err, token ) => {
					if ( err ) {
						return reject( err );
					}
					client.token = token; // TODO Nicer way to persist this?
					resolve();
				} );
			} );
		} );
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

		return this.loginAndGetToken()
			.then( () => {
				return new Promise( ( resolve, reject ) => {
					if ( lexeme.lexicalCategory !== null ) { // optionally skip creation
						resolve();
					}

					client.api.call(
						{
							action: 'wbeditentity',
							'new': 'item',
							data: JSON.stringify( {} ),
							token: client.token
						},
						( err, _, __, payload ) => {
							if ( err ) {
								return reject( err );
							}

							lexeme.lexicalCategory = payload.entity.id;

							resolve();
						},
						METHOD_POST
					);
				} );
			} ).then( () => {
				return new Promise( ( resolve, reject ) => {
					if ( lexeme.language !== null ) { // optionally skip creation
						resolve();
					}

					client.api.call(
						{
							action: 'wbeditentity',
							'new': 'item',
							data: JSON.stringify( {} ),
							token: client.token
						},
						( err, _, __, payload ) => {
							if ( err ) {
								return reject( err );
							}

							lexeme.language = payload.entity.id;

							resolve();
						},
						METHOD_POST
					);
				} );
			} ).then( () => {
				return new Promise( ( resolve, reject ) => {
					client.api.call(
						{
							action: 'wbeditentity',
							'new': 'lexeme',
							data: JSON.stringify( lexeme ),
							token: client.token
						},
						( err, _, __, payload ) => {
							if ( err ) {
								return reject( err );
							}

							resolve( payload.entity );
						},
						METHOD_POST
					);
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
			client.api.call(
				{
					action: 'wbgetentities',
					ids: lexemeId
				},
				( err, _, __, response ) => {
					if ( err ) {
						return reject( err );
					}
					resolve( response.entities[ lexemeId ] );
				},
				METHOD_POST
			);
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
		return new Promise( ( resolve, reject ) => {
			client.api.call(
				{
					action: 'wbladdform',
					lexemeId: lexemeId,
					data: JSON.stringify( form ),
					token: client.token
				},
				( err, _, __, response ) => {
					if ( err ) {
						return reject( err );
					}
					resolve( response );
				},
				METHOD_POST
			);
		} );
	}

}

module.exports = new LexemeApi();
