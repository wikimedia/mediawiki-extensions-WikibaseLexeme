/**
 * @license GPL-2.0-or-later
 */
( function ( mw, wb, $ ) {
	'use strict';

	/**
	 * @constructor
	 *
	 * @param {mediaWiki.Api} api
	 * @param {wikibase.lexeme.RevisionStore} revisionStore
	 * @param {string} lexemeId
	 * @param {Object} senseData
	 */
	var SELF = wb.lexeme.entityChangers.SenseChanger = function WbLexemeSenseChanger(
		api,
		revisionStore,
		lexemeId,
		senseData
	) {
		this.api = api;
		this.revisionStore = revisionStore;
		this.lexemeId = lexemeId;
		this.senseData = senseData;
		this.lexemeDeserializer = new wb.lexeme.serialization.LexemeDeserializer();
		this.senseSerializer = new wb.lexeme.serialization.SenseSerializer();
	};

	/**
	 * @class wikibase.lexeme.entityChangers.SenseChanger
	 */
	$.extend( SELF.prototype, {

		/**
		 * @type {mediaWiki.Api}
		 * @private
		 */
		api: null,

		/**
		 * @type {wikibase.lexeme.RevisionStore}
		 * @private
		 */
		revisionStore: null,

		/**
		 * @type {string}
		 * @private
		 */
		lexemeId: null,

		/**
		 * @type {Object}
		 * @private
		 */
		senseData: null,

		/**
		 * @type {wikibase.lexeme.serialization.LexemeDeserializer}
		 * @private
		 */
		lexemeDeserializer: null,

		/**
		 * @param {wikibase.lexeme.datamodel.Sense} sense
		 * @return {jQuery.Promise}
		 */
		save: function ( sense ) {
			var senseSerializer = new wb.lexeme.serialization.SenseSerializer();

			var serializedSense = senseSerializer.serialize( sense );
			return this.saveNewSenseData( serializedSense.glosses );
		},

		saveNewSenseData: function ( glosses ) {
			var self = this;

			return this.api.postWithToken( 'csrf', {
				action: 'wbladdsense',
				lexemeId: this.lexemeId,
				data: JSON.stringify( { glosses: glosses } ),
				errorformat: 'plaintext',
				bot: 0
			} ).then( function ( data ) {
				var sense = self.lexemeDeserializer.deserializeSense( data.sense );
				self.revisionStore.setSenseRevision( data.lastrevid, sense.getId() );
				self.senseData = self.senseSerializer.serialize( sense );
				return sense;
			} ).catch( function ( code, response ) {
				throw convertPlainTextErrorsToRepoApiError( response.errors, 'save' );
			} );
		}
	} );

	function convertPlainTextErrorsToRepoApiError( errors, action ) {
		var $ul = $( '<ul>' );

		var code = '';
		errors.forEach( function ( e ) {
			if ( !code ) {
				code = e.code;
			}

			var $li = $( '<li>' ).text( e[ '*' ] );
			$ul.append( $li );
		} );

		return new wb.api.RepoApiError(
			code,
			$ul.html(),
			[],
			action
		);
	}

}( mediaWiki, wikibase, jQuery ) );
