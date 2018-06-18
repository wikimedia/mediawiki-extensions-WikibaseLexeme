/**
 * @license GPL-2.0-or-later
 */
describe( 'wikibase.lexeme.widgets.LexemeHeader', function () {
	var sinon = require( 'sinon' );
	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );
	expect.installPlugin( require( 'unexpected-sinon' ) );

	var Vue = global.Vue = require( 'vue/dist/vue.js' ); // eslint-disable-line no-restricted-globals
	var Vuex = global.Vuex = require( 'vuex/dist/vuex.js' ); // eslint-disable-line no-restricted-globals
	Vue.use( Vuex );

	var newLexemeHeader = require( 'wikibase.lexeme.widgets.LexemeHeader.newLexemeHeader' );
	var newLexemeHeaderStore = require( 'wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );
	var LemmaList = require( 'wikibase.lexeme.datatransfer.LemmaList' );

	it( 'switch to edit mode', function ( done ) {
		var widget = newWidget( { lemmas: [] } );

		widget.edit();
		widget.$nextTick( function () {
			expect( widget, 'to be in edit mode' );
			done();
		} );
	} );

	it( 'cancel edit mode', function ( done ) {
		var widget = newWidget( { lemmas: [] } );

		widget.edit();
		widget.cancel();
		widget.$nextTick( function () {
			expect( widget, 'not to be in edit mode' );
			done();
		} );
	} );

	it( 'save lemma list', function ( done ) {
		var lexeme = { lemmas: [ new Lemma( 'hello', 'en' ) ] },
			store = newStore( lexeme ),
			widget = newWidgetWithStore( store ),
			storeSpy = sinon.stub( store, 'dispatch' ).callsFake( function () {
				return Promise.resolve();
			} );

		widget.edit();
		widget.save().then( function () {
			expect( storeSpy, 'to have a call satisfying', [ 'save', lexeme ] );
			expect( widget, 'not to be in edit mode' );
			done();
		} );
	} );

	it( 'save lemma list with error', function ( done ) {
		var lexeme = { lemmas: [ new Lemma( 'hello', 'en' ) ] },
			store = newStore( lexeme ),
			widget = newWidgetWithStore( store ),
			storeSpy = sinon.stub( store, 'dispatch' ).callsFake( function () {
				return Promise.reject();
			} );

		widget.edit();
		widget.save();
		widget.$nextTick( function () {
			expect( storeSpy, 'to have a call satisfying', [ 'save', lexeme ] );
			expect( widget, 'to be in edit mode' );
			done();
		} );
	} );

	it( 'attempting to save with empty lemmas fails', function ( done ) {
		var lexeme = { lemmas: [] },
			store = newStore( lexeme ),
			widget = newWidgetWithStore( store ),
			storeSpy = sinon.stub( store, 'dispatch' );

		widget.edit();
		widget.save();

		widget.$nextTick( function () {
			expect( storeSpy.notCalled, 'to be true' );
			expect( widget, 'to be in edit mode' );
			done();
		} );
	} );

	it( 'passes lemmas to LemmaWidget', function () {
		var lemmas = [ new Lemma( 'hello', 'en' ) ],
			widget = newWidget( { lemmas: lemmas } );

		expect( widget.$children[ 0 ].lemmas, 'to equal', new LemmaList( lemmas ) ); // TODO: find a better way to do this
	} );

	it( 'passes language and lexical category to LanguageAndLexicalCategoryWidget', function () {
		var language = 'Q123',
			lexicalCategory = 'Q234',
			widget = newWidget( { lemmas: [], language: language, lexicalCategory: lexicalCategory } );

		expect( widget.$children[ 1 ].language, 'to equal', language ); // TODO: find a better way to do this
		expect( widget.$children[ 1 ].lexicalCategory, 'to equal', lexicalCategory ); // TODO: find a better way to do this
	} );

	it( 'shows save button disabled without changes', function ( done ) {
		var widget = newWidget( { lemmas: [] } );
		widget.edit();
		widget.$nextTick( function () {
			expect( widget.$el.querySelector( '.lemma-widget_save' ), 'to have attributes', { disabled: 'disabled' } );
			done();
		} );
	} );

	describe( 'hasChanges', function () {
		it( 'returns false by default', function () {
			var widget = newWidget( { lemmas: [], language: 'Q123', lexicalCategory: 'Q321' } );
			expect( widget.hasChanges, 'to be false' );
		} );

		it( 'returns true when language changes', function () {
			var widget = newWidget( { lemmas: [], language: 'Q123', lexicalCategory: 'Q321' } );
			widget.language = 'Q234';
			expect( widget.hasChanges, 'to be true' );
		} );

		it( 'returns true when lexical category changes', function () {
			var widget = newWidget( { lemmas: [], language: 'Q123', lexicalCategory: 'Q321' } );
			widget.lexicalCategory = 'Q432';
			expect( widget.hasChanges, 'to be true' );
		} );

		it( 'returns true when lemmas change', function () {
			var widget = newWidget( { lemmas: [], language: 'Q123', lexicalCategory: 'Q321' } );
			widget.lemmas.add( new Lemma( 'en', 'hamster' ) );
			expect( widget.hasChanges, 'to be true' );
		} );

		it( 'ignores added empty lemmas', function () {
			var widget = newWidget( { lemmas: [], language: 'Q123', lexicalCategory: 'Q321' } );
			widget.lemmas.add( new Lemma( '', '' ) );
			expect( widget.hasChanges, 'to be false' );
		} );
	} );

	expect.addAssertion( '<object> [not] to be in edit mode', function ( expect, widget ) {
		expect.errorMode = 'nested';

		expect( widget.inEditMode, '[not] to be true' );
		var no = expect.flags.not ? ' no ' : ' ';
		expect( widget.$el, 'to contain' + no + 'elements matching', '.lemma-widget_save' );
	} );

	function newWidget( lexeme ) {
		return newWidgetWithStore( newStore( lexeme ) );
	}

	function newStore( lexeme ) {
		return new Vuex.Store( newLexemeHeaderStore( {}, lexeme, 0 ) );
	}

	function newWidgetWithStore( store ) {
		var element = document.createElement( 'div' );

		return new Vue( newLexemeHeader(
			store,
			element,
			getTemplate(),
			getLemmaWidget(),
			getLanguageAndLexicalCategoryWidget(),
			{
				get: function ( key ) {
					return key;
				}
			}
		) ).$mount();
	}

	function getLemmaWidget() {
		return Vue.component( 'lemma-widget', {
			props: [ 'lemmas', 'inEditMode', 'isSaving' ],
			template: '<div></div>'
		} );
	}

	function getLanguageAndLexicalCategoryWidget() {
		return {
			props: [ 'language', 'lexicalCategory', 'inEditMode', 'isSaving' ],
			template: '<div></div>'
		};
	}

	// FIXME: duplicated from LexemeView.php until it's reusable
	function getTemplate() {
		return '<div>'
			+ '<h1 id="wb-lexeme-header" class="wb-lexeme-header">'
			+ '<div class="wb-lexeme-header_id">({{id}})</div><!-- TODO: i18n parentheses -->'
			+ '<div class="wb-lexeme-header_lemma-widget">'
			+ '<lemma-widget :lemmas="lemmas" :inEditMode="inEditMode" :isSaving="isSaving"></lemma-widget>'
			+ '</div>'
			+ '<div class="lemma-widget_controls" v-if="isInitialized" >'
			+ '<button type="button" class="lemma-widget_edit" v-if="!inEditMode" '
			+ ' :disabled="isSaving" v-on:click="edit">{{\'wikibase-edit\'|message}}</button>'
			+ '<button type="button" class="lemma-widget_save" v-if="inEditMode" '
			+ ' :disabled="isSaving || !hasChanges" v-on:click="save">{{\'wikibase-save\'|message}}</button>'
			+ '<button type="button" class="lemma-widget_cancel" v-if="inEditMode" '
			+ ' :disabled="isSaving"  v-on:click="cancel">{{\'wikibase-cancel\'|message}}</button>'
			+ '</div>'
			+ '</h1>'
			+ '<language-and-category-widget '
			+ '	:language.sync="language"'
			+ '	:lexicalCategory.sync="lexicalCategory"'
			+ '	:inEditMode="inEditMode"'
			+ '	:isSaving="isSaving">'
			+ '</language-and-category-widget>'
			+ '</div>';

	}

} );
