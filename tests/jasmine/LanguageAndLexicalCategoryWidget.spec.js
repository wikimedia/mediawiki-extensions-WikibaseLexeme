/**
 * @license GPL-2.0+
 */
describe( 'wikibase.lexeme.widgets.LanguageAndLexicalCategoryWidget', function () {
	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );

	var Vue = global.Vue = require( 'vue/dist/vue.js' ); // eslint-disable-line no-restricted-globals
	var Vuex = global.Vuex = require( 'vuex/dist/vuex.js' ); // eslint-disable-line no-restricted-globals
	Vue.use( Vuex );

	var newLanguageAndLexicalCategoryWidget = require( 'wikibase.lexeme.widgets.LanguageAndLexicalCategoryWidget' );

	it( 'shows the language and the lexical category', function () {
		var language = 'Q123',
			lexicalCategory = 'Q234',
			widget = newWidget( language, lexicalCategory );

		expect( widget.$el.textContent, 'to contain', language );
		expect( widget.$el.textContent, 'to contain', lexicalCategory );
	} );

	it( 'switches to edit mode and back', function ( done ) {
		var widget = newWidget( 'Q123', 'Q234' );

		expect( widget, 'not to be in edit mode' );

		widget.inEditMode = true;
		widget.$nextTick( function () {
			expect( widget, 'to be in edit mode' );

			widget.inEditMode = false;
			widget.$nextTick( function () {
				expect( widget, 'not to be in edit mode' );
				done();
			} );
		} );
	} );

	expect.addAssertion( '<object> [not] to be in edit mode', function ( expect, widget ) {
		expect.errorMode = 'nested';

		expect( widget.inEditMode, '[not] to be true' );
		var no = expect.flags.not ? ' no ' : ' ';
		expect( widget.$el, 'to contain' + no + 'elements matching', 'input' );
	} );

	function newWidget( language, lexicalCategory ) {
		var LanguageAndLexicalCategoryWidget = Vue.extend( newLanguageAndLexicalCategoryWidget( getTemplate(), {
			get: function ( key ) {
				return key;
			}
		} ) );

		return new LanguageAndLexicalCategoryWidget( {
			propsData: {
				language: language,
				lexicalCategory: lexicalCategory,
				inEditMode: false,
				isSaving: false
			}
		} ).$mount();
	}

	function getTemplate() {
		return '<div class="language-lexical-category-widget">'
			+ '<div v-if="!inEditMode">'
			+ '<div>'
			+ '<span>{{\'wikibase-lexeme-language\'|message}}</span>'
			+ '<span>{{language}}</span>'
			+ '</div>'
			+ '<div>'
			+ '<span>{{\'wikibase-lexeme-lexical-category\'|message}}</span>'
			+ '<span>{{lexicalCategory}}</span>'
			+ '</div>'
			+ '</div>'
			+ '<div v-else>'
			+ '<div>'
			+ '<label for="lexeme-language">{{\'wikibase-lexeme-language\'|message}}</label>'
			+ '<input id="lexeme-language" v-bind:value="language" @input="$emit(\'update:language\', $event.target.value)">'
			+ '</div>'
			+ '<div>'
			+ '<label for="lexeme-lexical-category">{{\'wikibase-lexeme-lexical-category\'|message}}</label>'
			+ '<input id="lexeme-lexical-category" v-bind:value="lexicalCategory" @input="$emit(\'update:lexicalCategory\', $event.target.value)">'
			+ '</div>'
			+ '</div>'
			+ '</div>';
	}
} );
