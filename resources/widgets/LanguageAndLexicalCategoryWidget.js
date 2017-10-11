module.exports = ( function () {
	'use strict';

	var ItemSelectorWrapper = require( 'wikibase.lexeme.widgets.ItemSelectorWrapper' );

	/**
	 * @callback wikibase.lexeme.widgets.LanguageAndLexicalCategoryWidget
	 *
	 * @param {string} template - template string or selector
	 * @param {Object} messages - mw.messages localization service
	 */
	return function ( template, messages ) {
		return {
			props: [ 'language', 'lexicalCategory', 'inEditMode', 'isSaving' ],
			template: template,
			components: {
				'item-selector': ItemSelectorWrapper
			},

			filters: {
				message: function ( key ) {
					return messages.get( key );
				}
			}
		};
	};
} )();
