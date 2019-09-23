module.exports = ( function () {
	'use strict';

	var ItemSelectorWrapper = require( './ItemSelectorWrapper.js' );

	/**
	 * @callback LanguageAndLexicalCategoryWidget
	 *
	 * @param {string} template - template string or selector
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {Object} messages - mw.messages localization service
	 */
	return function ( template, api, messages ) {
		return {
			props: [ 'language', 'lexicalCategory', 'inEditMode', 'isSaving' ],
			template: template,
			components: {
				'item-selector': ItemSelectorWrapper( api )
			},

			filters: {
				message: function ( key ) {
					return messages.get( key );
				}
			},
			computed: {
				formattedLanguage: function () {
					return this.$store.state.languageLink;
				},
				formattedLexicalCategory: function () {
					return this.$store.state.lexicalCategoryLink;
				}
			}
		};
	};
} )();
