module.exports = ( function () {
	'use strict';

	var focusElement = require( '../focusElement.js' );

	/**
	 * @callback wikibase.lexeme.widgets.LemmaWidget.newComponent
	 *
	 * @param {Vuex.Store} store
	 * @param {string} template - template string or selector
	 * @param {Object} lemmaWidget
	 * @param {Object} languageAndCategoryWidget
	 * @param {Object} messages - mw.messages localization service
	 * @return {Object}
	 */
	return function ( store, template, lemmaWidget, languageAndCategoryWidget, messages ) {
		return {
			compatConfig: { MODE: 3 },
			template: template,

			data: function () {
				return {
					isInitialized: true,
					inEditMode: false,
					id: store.state.id,
					lemmas: store.state.lemmas.copy(),
					hasRedundantLemmaLanguage: false,
					language: store.state.language,
					lexicalCategory: store.state.lexicalCategory
				};
			},

			methods: {
				handleEnter: function () {
					if ( this.inEditMode && !this.isUnsaveable ) {
						this.save();
					}
				},
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
						this.lemmas = this.$store.state.lemmas.copy();
						this.language = this.$store.state.language;
						this.lexicalCategory = this.$store.state.lexicalCategory;
					}
						.bind( this ) )
						.catch( function ( error ) {
							this.displayError( error );
						}.bind( this ) );
				},

				edit: function () {
					this.inEditMode = true;
					this.$nextTick( focusElement( 'input' ) );
				},

				cancel: function () {
					this.inEditMode = false;
					this.lemmas = this.$store.state.lemmas.copy();
					this.language = this.$store.state.language;
					this.lexicalCategory = this.$store.state.lexicalCategory;
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
				},

				message: function ( key ) {
					return messages.get( key );
				}
			},

			computed: {
				isSaving: function () {
					return store.state.isSaving;
				},

				hasChanges: function () {
					return this.language !== this.$store.state.language
						|| this.lexicalCategory !== this.$store.state.lexicalCategory
						|| !this.lemmas.equals( this.$store.state.lemmas );
				},

				isUnsaveable: function () {
					return !this.hasChanges
						|| this.isSaving
						|| this.hasRedundantLemmaLanguage;
				}
			},

			components: {
				'lemma-widget': lemmaWidget,
				'language-and-category-widget': languageAndCategoryWidget
			}
		};
	};
}() );
