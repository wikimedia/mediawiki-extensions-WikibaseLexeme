module.exports = ( function () {
	'use strict';

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

			filters: {
				message: function ( key ) {
					return messages.get( key );
				}
			}
		};
	};
} )();
