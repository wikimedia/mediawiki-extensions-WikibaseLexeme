module.exports = ( function ( require, wb ) {
	'use strict';

	var Vue = require( 'vue' ),
		RedundantLanguageIndicator = require( './RedundantLanguageIndicator.js' ),
		InvalidLanguageIndicator = require( './InvalidLanguageIndicator.js' ),
		LanguageSelectorWrapper = require( './LanguageSelectorWrapper.js' ),
		focusElement = require( '../focusElement.js' ),
		// languages.json is a dynamic ResourceLoader source file
		lexemeTermLanguages = require( './languages.json' ).lexemeTermLanguages;

	function deepClone( object ) {
		return JSON.parse( JSON.stringify( object ) ).sort( function ( a, b ) {
			return a.language > b.language;
		} );
	}

	function applyGlossWidget( widgetElement, glosses, beforeUpdate, mw, getDirectionality ) {
		var template = mw.template.get( 'wikibase.lexeme.lexemeview', 'glossWidget.vue' ).getSource();
		var messages = mw.messages;
		var fragment = document.createDocumentFragment();

		// make the app replace the widgetElement (like in Vue 2) instead of appending to it (Vue 3 mount behavior)
		var app = Vue.createMwApp( newGlossWidget( messages, template, glosses, beforeUpdate, getDirectionality ) )
			.mount( fragment );
		widgetElement.replaceWith( fragment );

		return app;
	}

	/**
	 * @param {mw.messages} messages
	 * @param {string} template
	 * @param {[{ value: string, language: string }]} glosses
	 * @param {Function} beforeUpdate
	 * @param {Function} getDirectionality
	 * @return {Object}
	 */
	function newGlossWidget( messages, template, glosses, beforeUpdate, getDirectionality ) {
		return {
			compatConfig: { MODE: 3 },
			template: template,

			mixins: [
				RedundantLanguageIndicator( 'glosses' ),
				InvalidLanguageIndicator( 'glosses', lexemeTermLanguages )
			],

			components: {
				'language-selector': LanguageSelectorWrapper( lexemeTermLanguages )
			},

			beforeUpdate: beforeUpdate,

			data: function () {
				return {
					inEditMode: false,
					glosses: deepClone( glosses )
				};
			},
			methods: {
				add: function () {
					this.glosses.push( { value: '', language: '' } );
					this.$nextTick( focusElement( 'tr:last-child input' ) );
				},
				remove: function ( gloss ) {
					var index = this.glosses.indexOf( gloss );
					this.glosses.splice( index, 1 );
				},
				edit: function () {
					this.inEditMode = true;
					if ( this.glosses.length === 0 ) {
						this.add();
					}
					this.$nextTick( focusElement( 'input' ) );
				},
				stopEditing: function () {
					this.inEditMode = false;
					this.glosses = this.glosses.filter( function ( gloss ) {
						return gloss.value.trim() !== '' && gloss.language.trim() !== '';
					} );
				},
				message: function ( key ) {
					return messages.get( key );
				},
				directionality: function ( languageCode ) {
					return getDirectionality( languageCode );
				},
				languageName: function ( languageCode ) {
					return wb.getLanguageNameByCode( languageCode );
				}
			}
		};
	}

	return {
		applyGlossWidget: applyGlossWidget,
		newGlossWidget: newGlossWidget
	};

}( require, wikibase ) );
