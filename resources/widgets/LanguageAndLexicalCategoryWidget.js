module.exports = ( function () {
	'use strict';

	var ItemSelectorWrapper = require( './ItemSelectorWrapper.js' );

	/**
	 * @callback LanguageAndLexicalCategoryWidget
	 *
	 * @param {string} template - template string or selector
	 * @param {wikibase.api.RepoApi} api
	 * @param {Object} messages - mw.messages localization service
	 * @return {Object}
	 */
	return function ( template, api, messages ) {
		return {
			compatConfig: { MODE: 3 },
			props: [ 'language', 'lexicalCategory', 'inEditMode', 'isSaving' ],
			template: template,
			components: {
				'item-selector': ItemSelectorWrapper( api )
			},

			methods: {
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
}() );
