module.exports = ( function () {
	'use strict';

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
				lemmas: store.state.lemmas.copy(),
				language: store.state.language,
				lexicalCategory: store.state.lexicalCategory
			},

			methods: {
				save: function () {
					if ( this.lemmas.length() === 0 ) {
						this.displayEmptyLemmasError();
						return;
					}

					return store.dispatch(
						'save',
						{
							lemmas: this.lemmas.getLemmas(),
							lexicalCategory: this.lexicalCategory,
							language: this.language
						}
					).then( function () {
						this.inEditMode = false;
					}.bind( this ) )
					.catch( function ( code, response ) {
						this.displayError( response.error );
					}.bind( this ) );
				},

				edit: function () {
					this.inEditMode = true;
				},

				cancel: function () {
					this.inEditMode = false;
					this.lemmas = this.$store.state.lemmas.copy();
				},

				/**
				 * This method is overridden in LexemeHeader.js
				 */
				displayError: function () {},

				/**
				 * TODO: This should ideally be an error that comes from the API
				 */
				displayEmptyLemmasError: function () {
					this.displayError( {
						code: 'save-failed',
						info: messages.get( 'wikibaselexeme-error-cannot-remove-last-lemma' )
					} );
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
