module.exports = ( function () {
	'use strict';

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
	 * @param {Object} lemmaWidget
	 * @param {Object} languageAndCategoryWidget
	 * @param {Object} messages - mw.messages localization service
	 */
	return function ( store, element, template, lemmaWidget, languageAndCategoryWidget, messages ) {
		return {
			el: element,
			template: template,
			store: store,

			data: {
				isInitialized: true,
				inEditMode: false,
				id: store.state.id,
				lemmas: copyLemmaList( store.state.lemmas ),
				language: store.state.language,
				lexicalCategory: store.state.lexicalCategory
			},

			methods: {
				save: function () {
					return store.dispatch(
						'save',
						{
							lemmas: this.lemmas,
							lexicalCategory: this.lexicalCategory,
							language: this.language
						}
					).then( function () {
						this.inEditMode = false;
					}.bind( this ) );
				},

				edit: function () {
					this.inEditMode = true;
				},

				cancel: function () {
					this.inEditMode = false;
					this.lemmas = copyLemmaList( store.state.lemmas );
				}
			},

			computed: {
				isSaving: function () {
					return store.state.isSaving;
				}
			},

			components: {
				'lemma-widget': lemmaWidget,
				'language-and-category-widget': languageAndCategoryWidget
			},

			filters: {
				message: function ( key ) {
					return messages.get( key );
				}
			}
		};
	};
} )();
