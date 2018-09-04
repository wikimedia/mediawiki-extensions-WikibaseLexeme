/**
 * @license GPL-2.0-or-later
 */
describe( 'wikibase.lexeme.widgets.LemmaWidget', function () {
	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );

	var Vue = global.Vue = require( 'vue/dist/vue.js' ); // eslint-disable-line no-restricted-globals
	var Vuex = global.Vuex = require( 'vuex/dist/vuex.js' ); // eslint-disable-line no-restricted-globals
	Vue.use( Vuex );

	var newLemmaWidget = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );
	var LemmaList = require( 'wikibase.lexeme.datatransfer.LemmaList' );

	var selector = {
		lemma: '.lemma-widget_lemma',
		lemmaValue: '.lemma-widget_lemma-value',
		lemmaLanguage: '.lemma-widget_lemma-language'
	};

	it( 'initialize widget with one lemma', function () {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
		expect( widget, 'not to be in edit mode' );
	} );

	it( 'edit mode is true', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		expect( widget, 'not to be in edit mode' );

		widget.inEditMode = true;
		widget.$nextTick( function () {
			expect( widget, 'to be in edit mode' );
			done();
		} );
	} );

	it( 'edit mode is false', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		widget.inEditMode = false;
		widget.$nextTick( function () {
			expect( widget, 'not to be in edit mode' );
			done();
		} );
	} );

	it( 'add a new lemma', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
		widget.add();
		widget.$nextTick( function () {
			expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
			expect( widget.$el, 'to contain lemma', new Lemma( '', '' ) );
			done();
		} );
	} );

	it( 'remove a lemma', function ( done ) {
		var lemmaToRemove = new Lemma( 'hello', 'en' ),
			widget = newWidget( [ lemmaToRemove ] );

		expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
		widget.remove( lemmaToRemove );
		widget.$nextTick( function () {
			expect( widget.$el, 'to contain no lemmas' );
			done();
		} );
	} );

	it( 'can carry redundant lemma languages', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ), new Lemma( 'world', 'en' ) ] );

		widget.$nextTick( function () {
			expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
			expect( widget.$el, 'to contain lemma', new Lemma( 'world', 'en' ) );
			done();
		} );
	} );

	it( 'detects redundant lemma language to mark the individual languages', function () {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ), new Lemma( 'world', 'en' ) ] );

		expect( widget.isRedundantLanguage( 'en' ), 'to be true' );
		expect( widget.isRedundantLanguage( 'fr' ), 'to be false' );
	} );

	it( 'detects redundant lemma languages to mark the widget', function () {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ), new Lemma( 'world', 'en' ) ] );

		expect( widget.hasRedundantLanguage, 'to be true' );
	} );

	it( 'marks-up the lemma term with the lemma language', function ( done ) {
		var widget = newWidget( [ new Lemma( 'colour', 'en-GB' ) ] );

		widget.$nextTick( function () {
			expect( widget.$el.querySelector( selector.lemmaValue ), 'to have attributes', { lang: 'en-GB' } );
			done();
		} );
	} );

	expect.addAssertion( '<DOMElement> to contain lemma <object>', function ( expect, element, lemma ) {
		var language = lemma.language;
		var value = lemma.value;
		expect.errorMode = 'nested';
		expect(
			element,
			'when queried for', selector.lemma + ' ' + selector.lemmaValue,
			'to have an item satisfying', 'to have text', value );
		expect(
			element,
			'when queried for', selector.lemma + ' ' + selector.lemmaLanguage,
			'to have an item satisfying', 'to have text', language );
	} );

	expect.addAssertion( '<DOMElement> to contain [no] lemmas', function ( expect, element ) {
		expect.errorMode = 'nested';
		expect( element, 'to contain [no] elements matching', selector.lemma );
	} );

	expect.addAssertion( '<object> [not] to be in edit mode', function ( expect, widget ) {
		expect.errorMode = 'nested';

		expect( widget.inEditMode, '[not] to be true' ); // TODO: why test internals?
		var no = expect.flags.not ? ' no ' : ' ';
		expect( widget.$el, 'to contain' + no + 'elements matching', 'input' );
	} );

	function newWidget( lemmas ) {
		var LemmaWidget = Vue.extend( newLemmaWidget( getTemplate(), {
			get: function ( key ) {
				return key;
			}
		} ) );

		return new LemmaWidget( { propsData: {
			lemmas: new LemmaList( lemmas ),
			inEditMode: false,
			isSaving: false
		} } ).$mount();
	}

	function getTemplate() {
		return '<div class="lemma-widget">'
			+ '<ul v-if="!inEditMode" class="lemma-widget_lemma-list">'
			+ '<li v-for="lemma in lemmaList" class="lemma-widget_lemma">'
			+ '<span class="lemma-widget_lemma-value" :lang="lemma.language">{{lemma.value}}</span>'
			+ '<span class="lemma-widget_lemma-language">{{lemma.language}}</span>'
			+ '</li>'
			+ '</ul>'
			+ '<div v-else class="lemma-widget_edit-area">'
			+ '<ul class="lemma-widget_lemma-list">'
			+ '<li v-for="lemma in lemmaList" class="lemma-widget_lemma-edit-box">'
			+ '<span class="lemma-widget_lemma-value-label">'
			+ '{{\'wikibaselexeme-lemma-field-lemma-label\'|message}}'
			+ '</span>'
			+ '<input size="1" class="lemma-widget_lemma-value-input" '
			+ 'v-model="lemma.value" :disabled="isSaving">'
			+ '<span class="lemma-widget_lemma-language-label">'
			+ '{{\'wikibaselexeme-lemma-field-language-label\'|message}}'
			+ '</span>'
			+ '<input size="1" class="lemma-widget_lemma-language-input" '
			+ 'v-model="lemma.language" :disabled="isSaving"'
			+ ':class="{'
			+ '\'lemma-widget_lemma-language-input_redundant-language\': '
			+ 'isRedundantLanguage(lemma.language)'
			+ '}'
			+ ':aria-invalid="isRedundantLanguage(lemma.language)">'
			+ '<button class="lemma-widget_lemma-remove" v-on:click="remove(lemma)" '
			+ ':disabled="isSaving" :title="\'wikibase-remove\'|message">'
			+ '&times;'
			+ '</button>'
			+ '</li>'
			+ '<li>'
			+ '<button type="button" class="lemma-widget_add" v-on:click="add" '
			+ ':disabled="isSaving" :title="\'wikibase-add\'|message">+</button>'
			+ '</li>'
			+ '</ul>'
			+ '</div>'
			+ '</div>';
	}
} );
