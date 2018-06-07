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
					this.lemmas.add( new Lemma( '', '' ) );
				},
				remove: function ( lemma ) {
					this.lemmas.remove( lemma );
				}
			},

			filters: {
				message: function ( key ) {
					return messages.get( key );
				}
			},

			computed: {
				/**
				 * This only exists because the PHP template can't handle lemmas.getLemmas().
				 *
				 * @returns {Lemma[]}
				 */
				lemmaList: function () {
					return this.lemmas.getLemmas();
				}
			}
		};
	};
} )();
