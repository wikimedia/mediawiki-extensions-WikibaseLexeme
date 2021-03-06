'use strict';

const MWBot = require( 'mwbot' ),
	request = require( 'request' ),
	Util = require( 'wdio-mediawiki/Util' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

class LexemeApi {

	/**
	 * Initialize the API
	 *
	 * @param {string} [cpPosIndex] The value of the cpPosIndex browser cookie.
	 * Optional, but strongly recommended to have chronology protection.
	 * @return {Promise}
	 */
	initialize( cpPosIndex ) {
		const jar = request.jar();
		if ( cpPosIndex ) {
			const cookie = request.cookie( 'cpPosIndex=' + cpPosIndex );
			jar.setCookie( cookie, browser.config.baseUrl );
		}
		const bot = new MWBot(
			{
				apiUrl: browser.config.baseUrl + '/api.php'
			},
			{
				jar: jar
			}
		);
		return bot.loginGetEditToken( {
			username: browser.config.mwUser,
			password: browser.config.mwPwd
		} ).then( () => {
			this.bot = bot;
			return bot;
		} );
	}

	/**
	 * @private
	 * @return {Promise} resolving with MWBot
	 */
	getBot() {
		if ( !this.bot ) {
			console.trace( 'WARNING: LexemeApi not initialized' );
			return this.initialize();
		}

		return Promise.resolve( this.bot );
	}

	/**
	 * Create a lexeme
	 *
	 * @param {Object} lexeme Optional lexeme definition to merge into default definition
	 * @return {Promise}
	 */
	create( lexeme ) {
		lexeme = Object.assign( {
			lemmas: {
				en: {
					value: Util.getTestString(),
					language: 'en'
				}
			},
			lexicalCategory: null, // if null a new lexicalCategory is created and used for the lexeme
			language: null // if null a new language is created and used for the lexeme
		}, lexeme );
		// Avoid an ID acquisition race condition by executing one after another.
		return (
			lexeme.lexicalCategory === null ? WikibaseApi.createItem() : Promise.resolve( lexeme.lexicalCategory )
		).then( ( categoryValue ) => {
			lexeme.lexicalCategory = categoryValue;
			return ( lexeme.language === null ? WikibaseApi.createItem() : Promise.resolve( lexeme.language ) );
		} ).then( ( languageValue ) => {
			lexeme.language = languageValue;
			return this.getBot().then( ( bot ) => {
				return bot.request( {
					action: 'wbeditentity',
					new: 'lexeme',
					data: JSON.stringify( lexeme ),
					token: bot.editToken
				} ).then( ( payload ) => {
					return payload.entity;
				} );
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
		return this.getBot().then( ( bot ) => {
			return bot.request( {
				action: 'wbgetentities',
				ids: lexemeId
			} );
		} ).then( ( response ) => {
			return Promise.resolve( response.entities[ lexemeId ] );
		} );
	}

	/**
	 * Get the current replication lag.
	 *
	 * @return {Promise}
	 */
	getReplicationLag() {
		return this.getBot().then( ( bot ) => {
			return bot.request( {
				action: 'query',
				meta: 'siteinfo',
				siprop: 'dbrepllag'
			} );
		} ).then( ( response ) => {
			return Promise.resolve( Math.max( 0, response.query.dbrepllag[ 0 ].lag ) );
		} );
	}

	/**
	 * Add a new form to a lexeme
	 *
	 * @param {string} lexemeId
	 * @param {Object} form
	 * @return {Promise}
	 */
	addForm( lexemeId, form ) {
		return this.getBot().then( ( bot ) => {
			return bot.request( {
				action: 'wbladdform',
				lexemeId: lexemeId,
				data: JSON.stringify( form ),
				token: bot.editToken
			} );
		} );
	}

	/**
	 * Add a new sense to a lexeme
	 *
	 * @param {string} lexemeId
	 * @param {Object} sense
	 * @return {Promise}
	 */
	addSense( lexemeId, sense ) {
		return this.getBot().then( ( bot ) => {
			return bot.request( {
				action: 'wbladdsense',
				lexemeId: lexemeId,
				data: JSON.stringify( sense ),
				token: bot.editToken
			} );
		} );
	}

	/**
	 * Changes representation and grammatical features of the form
	 *
	 * @param {string} formId
	 * @param {Object} formData
	 * @return {Promise}
	 */
	editForm( formId, formData ) {
		return this.getBot().then( ( bot ) => {
			return bot.request( {
				action: 'wbleditformelements',
				formId: formId,
				data: JSON.stringify( formData ),
				token: bot.editToken
			} );
		} );
	}

}

module.exports = new LexemeApi();
