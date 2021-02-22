module.exports = ( function ( require, wb, Vuex ) {
	'use strict';

	var Vue = require( 'vue' ),
		RedundantLanguageIndicator = require( './RedundantLanguageIndicator.js' ),
		InvalidLanguageIndicator = require( './InvalidLanguageIndicator.js' ),
		LanguageSelectorWrapper = require( './LanguageSelectorWrapper.js' ),
		focusElement = require( '../focusElement.js' ),
		// languages.json is a dynamic ResourceLoader source file
		lexemeTermLanguages = require( './languages.json' ).lexemeTermLanguages;

	Vue.use( Vuex );

	function deepClone( object ) {
		return JSON.parse( JSON.stringify( object ) ).sort( function ( a, b ) {
			return a.language > b.language;
		} );
	}

	function applyGlossWidget( widgetElement, glosses, beforeUpdate, mw, getDirectionality ) {
		var template = mw.template.get( 'wikibase.lexeme.lexemeview', 'glossWidget.vue' ).getSource();
		var messages = mw.messages;

		return new Vue( newGlossWidget( messages, widgetElement, template, glosses, beforeUpdate, getDirectionality ) );
	}

	/**
	 *
	 * @param {mw.messages} messages
	 * @param {string|HTMLElement} widgetElement
	 * @param {string} template
	 * @param {[{ value: string, language: string }]} glosses
	 * @param {Function} beforeUpdate
	 * @param {Function} getDirectionality
	 * @return {Object}
	 */
	function newGlossWidget( messages, widgetElement, template, glosses, beforeUpdate, getDirectionality ) {
		return {
			el: widgetElement,
			template: template,

			mixins: [
				RedundantLanguageIndicator( 'glosses' ),
				InvalidLanguageIndicator( 'glosses', lexemeTermLanguages )
			],

			components: {
				'language-selector': LanguageSelectorWrapper( lexemeTermLanguages )
			},

			beforeUpdate: beforeUpdate,

			data: {
				inEditMode: false,
				glosses: deepClone( glosses )
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
				}
			},
			filters: {
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

} )( require, wikibase, Vuex );
