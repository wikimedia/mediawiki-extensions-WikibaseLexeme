module.exports = ( function ( mw ) {
	'use strict';

	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	function copyLemmaList( list ) {
		var result = [];
		list.forEach( function ( lemma ) {
			result.push( lemma.copy() );
		} );

		return result;
	}

	/**
	 * @callback wikibase.lexeme.widgets.LemmaWidget.newComponent
	 *
	 * @param {Vuex.Store} store
	 * @param {string} element
	 * @param {string} template - template string or selector
	 */
	return function ( store, element, template ) {
		return {
			el: element,
			template: template,
			data: {
				isInitialized: true,
				inEditMode: false,
				lemmas: copyLemmaList( store.state.lemmas )
			},
			computed: {
				isSaving: function () {
					return store.state.isSaving;
				}
			},
			methods: {
				edit: function () {
					this.inEditMode = true;
				},
				add: function () {
					this.lemmas.push( new Lemma( '', '' ) );
				},
				remove: function ( lemma ) {
					var index = this.lemmas.indexOf( lemma );
					this.lemmas.splice( index, 1 );
				},
				save: function () {
					return store.dispatch( 'save', this.lemmas ).then( function () {
						this.inEditMode = false;
					}.bind( this ) );
				},
				cancel: function () {
					this.inEditMode = false;
					this.lemmas = copyLemmaList( store.state.lemmas );
				}
			},
			filters: {
				message: function ( key ) {
					return mw.messages.get( key );
				}
			}
		};
	};
} )( mediaWiki );
