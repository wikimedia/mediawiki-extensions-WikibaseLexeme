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
	 */
	var SELF = wb.lexeme.entityChangers.FormChanger = function WbLexemeFormChanger(
		api,
		revisionStore,
		lexemeId
	) {
		this.api = api;
		this.revisionStore = revisionStore;
		this.lexemeId = lexemeId;
		this.lexemeDeserializer = new wb.lexeme.serialization.LexemeDeserializer();
	};

	/**
	 * @class wikibase.lexeme.entityChangers.FormChanger
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
		 * @type {wikibase.lexeme.serialization.LexemeDeserializer}
		 * @private
		 */
		lexemeDeserializer: null,

		/**
		 * @param {wikibase.lexeme.datamodel.Form} form
		 * @return {jQuery.Promise}
		 */
		save: function ( form ) {
			var formSerializer = new wb.lexeme.serialization.FormSerializer();

			var serializedForm = formSerializer.serialize( form );

			if ( form.getId() ) {
				return this.saveChangedFormData( form.getId(), serializedForm.representations, serializedForm.grammaticalFeatures );
			}

			return this.saveNewFormData( serializedForm.representations, serializedForm.grammaticalFeatures );
		},

		saveChangedFormData: function ( formId, representations, grammaticalFeatures ) {
			var self = this;

			return this.api.postWithToken( 'csrf', {
				action: 'wbleditformelements',
				formId: formId,
				data: JSON.stringify( {
					representations: representations,
					grammaticalFeatures: grammaticalFeatures
				} ),
				errorformat: 'plaintext',
				bot: 0
			} ).then( function ( data ) {
				return self.lexemeDeserializer.deserializeForm( data.form );
			} ).catch( function ( code, response ) {
				throw convertPlainTextErrorsToRepoApiError( response.errors, 'save' );
			} );
		},

		saveNewFormData: function ( representations, grammaticalFeatures ) {
			var self = this;

			return this.api.postWithToken( 'csrf', {
				action: 'wbladdform',
				lexemeId: this.lexemeId,
				data: JSON.stringify( {
					representations: representations,
					grammaticalFeatures: grammaticalFeatures
				} ),
				errorformat: 'plaintext',
				bot: 0
			} ).then( function ( data ) {
				var form = self.lexemeDeserializer.deserializeForm( data.form );
				self.revisionStore.setFormRevision( data.lastrevid, form.getId() );
				return form;
			} ).catch( function ( code, response ) {
				throw convertPlainTextErrorsToRepoApiError( response.errors, 'save' );
			} );
		},

		remove: function ( form ) {
			var deferred = $.Deferred();

			this.api.postWithToken( 'csrf', {
				action: 'wblremoveform',
				id: form.getId(),
				errorformat: 'plaintext',
				bot: 0
			} )
				.then( deferred.resolve )
				.fail( function ( code, response ) {
					deferred.reject( convertPlainTextErrorsToRepoApiError( response.errors, 'remove' ) );
				} );

			return deferred;
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
