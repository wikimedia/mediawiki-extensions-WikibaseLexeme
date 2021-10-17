/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	var SenseSerializer = require( '../serialization/SenseSerializer.js' ),
		getDeserializer = require( 'wikibase.lexeme.getDeserializer' );

	/**
	 * @constructor
	 *
	 * @param {wikibase.api.RepoApi} api
	 * @param {wikibase.lexeme.RevisionStore} revisionStore
	 * @param {string} lexemeId
	 * @param {Object} senseData
	 * @param {Array} tags
	 */
	var SELF = function WbLexemeSenseChanger(
		api,
		revisionStore,
		lexemeId,
		senseData,
		tags
	) {
		this.api = api;
		this.revisionStore = revisionStore;
		this.lexemeId = lexemeId;
		this.senseData = senseData;
		this.lexemeDeserializer = getDeserializer();
		this.senseSerializer = new SenseSerializer();
		this._tags = tags;
	};

	/**
	 * @class wikibase.lexeme.entityChangers.SenseChanger
	 */
	$.extend( SELF.prototype, {

		/**
		 * @type {wikibase.api.RepoApi}
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
		 * @property {string[]}
		 * @private
		 */
		_tags: null,

		/**
		 * Returns tags used for edits
		 *
		 * @return {string|Array}
		 */
		getTags: function () {
			if ( this._tags && this._tags.length ) {
				return this.api.normalizeMultiValue( this._tags );
			}

			return [];
		},

		/**
		 * @param {wikibase.lexeme.datamodel.Sense} sense
		 * @return {jQuery.Promise}
		 */
		save: function ( sense ) {
			var senseSerializer = new SenseSerializer();

			var serializedSense = senseSerializer.serialize( sense );

			if ( sense.getId() ) {
				return this.saveChangedSenseData( sense.getId(), serializedSense.glosses );
			}

			return this.saveNewSenseData( serializedSense.glosses );
		},

		saveChangedSenseData: function ( senseId, glosses ) {
			var self = this;

			var requestGlosses =
				this.getGlossDataForApiRequest( this.senseData.glosses, glosses );

			return this.api.post( {
				action: 'wbleditsenseelements',
				senseId: senseId,
				baserevid: this.revisionStore.getBaseRevision(),
				data: JSON.stringify( {
					glosses: requestGlosses
				} ),
				errorformat: 'plaintext',
				bot: 0,
				tags: this.getTags()
			} ).then( function ( data ) {
				var sense = self.lexemeDeserializer.deserializeSense( data.sense );
				self.senseData = self.senseSerializer.serialize( sense );
				return sense;
			} ).catch( function ( code, response ) {
				throw convertPlainTextErrorsToRepoApiError( response.errors, 'save' );
			} );
		},

		saveNewSenseData: function ( glosses ) {
			var self = this;

			return this.api.post( {
				action: 'wbladdsense',
				lexemeId: this.lexemeId,
				data: JSON.stringify( { glosses: glosses } ),
				errorformat: 'plaintext',
				bot: 0,
				tags: this.getTags()
			} ).then( function ( data ) {
				var sense = self.lexemeDeserializer.deserializeSense( data.sense );
				self.revisionStore.setSenseRevision( data.lastrevid, sense.getId() );
				self.senseData = self.senseSerializer.serialize( sense );
				return sense;
			} ).catch( function ( code, response ) {
				throw convertPlainTextErrorsToRepoApiError( response.errors, 'save' );
			} );
		},

		remove: function ( sense ) {
			var deferred = $.Deferred();

			this.api.post( {
				action: 'wblremovesense',
				baserevid: this.revisionStore.getBaseRevision(),
				id: sense.getId(),
				errorformat: 'plaintext',
				bot: 0,
				tags: this.getTags()
			} )
				.then( deferred.resolve )
				.fail( function ( code, response ) {
					deferred.reject( convertPlainTextErrorsToRepoApiError( response.errors, 'remove' ) );
				} );

			return deferred;
		},

		// TODO same as FormChanger's getRepresentationDataForApiRequest, extract somewhere
		getGlossDataForApiRequest: function ( oldGlosses, newGlosses ) {
			var result = {};
			var language;

			for ( language in newGlosses ) {
				var newGloss = newGlosses[ language ].value;

				if ( ( !( language in oldGlosses ) )
					|| ( oldGlosses[ language ].value !== newGloss )
				) {
					result[ language ] = {
						language: language,
						value: newGloss
					};
				}
			}
			for ( language in oldGlosses ) {
				if ( !( language in newGlosses ) ) {
					result[ language ] = {
						language: language,
						remove: ''
					};
				}
			}

			return result;
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

	module.exports = SELF;

}( wikibase ) );
