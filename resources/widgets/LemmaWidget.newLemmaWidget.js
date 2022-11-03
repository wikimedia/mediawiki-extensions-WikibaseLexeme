module.exports = ( function () {
	'use strict';

	var Lemma = require( '../datamodel/Lemma.js' ),
		RedundantLanguageIndicator = require( './RedundantLanguageIndicator.js' ),
		focusElement = require( '../focusElement.js' );

	/**
	 * @callback wikibase.lexeme.widgets.LemmaWidget.newComponent
	 *
	 * @param {string} template - template string or selector
	 * @param {Object} messages - mw.messages localization service
	 * @return {Object}
	 */
	return function ( template, messages ) {
		return {
			compatConfig: { MODE: 3 },
			props: [ 'lemmas', 'inEditMode', 'isSaving' ],
			template: template,

			mixins: [ RedundantLanguageIndicator( 'lemmaList' ) ],

			methods: {
				add: function () {
					this.lemmas.add( new Lemma( '', '' ) );
					this.$nextTick( focusElement( 'li:nth-last-child(2) input' ) );
				},
				remove: function ( lemma ) {
					this.lemmas.remove( lemma );
				},
				message: function ( key ) {
					return messages.get( key );
				}
			},

			computed: {
				/**
				 * @return {Lemma[]}
				 */
				lemmaList: function () {
					return this.lemmas.getLemmas();
				}
			}
		};
	};
}() );
