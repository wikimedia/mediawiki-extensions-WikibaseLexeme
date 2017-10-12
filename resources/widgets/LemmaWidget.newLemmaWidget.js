module.exports = ( function () {
	'use strict';

	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	/**
	 * @callback wikibase.lexeme.widgets.LemmaWidget.newComponent
	 *
	 * @param {Vuex.Store} store
	 * @param {string} element
	 * @param {string} template - template string or selector
	 */
	return function ( template, messages ) {
		return {
			props: [ 'lemmas', 'inEditMode', 'isSaving' ],
			template: template,

			methods: {
				add: function () {
					this.lemmas.push( new Lemma( '', '' ) );
				},
				remove: function ( lemma ) {
					var index = this.lemmas.indexOf( lemma );
					this.lemmas.splice( index, 1 );
				}
			},

			filters: {
				message: function ( key ) {
					return messages.get( key );
				}
			}
		};
	};
} )();
