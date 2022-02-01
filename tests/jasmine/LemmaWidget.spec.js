/**
 * @license GPL-2.0-or-later
 */
describe( 'wikibase.lexeme.widgets.LemmaWidget', function () {
	var getTemplate = require('./helpers/template-loader');
	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );

	var newLemmaWidget = require( './../../resources/widgets/LemmaWidget.newLemmaWidget.js' );
	var Lemma = require( './../../resources/datamodel/Lemma.js' );
	var LemmaList = require( './../../resources/datatransfer/LemmaList.js' );

	var selector = {
		lemma: '.lemma-widget_lemma',
		lemmaValue: '.lemma-widget_lemma-value',
		lemmaValueInput: '.lemma-widget_lemma-value-input',
		lemmaLanguage: '.lemma-widget_lemma-language'
	};
	var reactiveRootProps;

	it( 'initialize widget with one lemma', function () {
		var widget = newWidgetWithAccessibleMethods( [ new Lemma( 'hello', 'en' ) ] );

		expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
		expect( widget, 'not to be in edit mode' );
	} );

	it( 'edit mode is true', function ( done ) {
		var widget = newWidgetWithReactiveProps( [ new Lemma( 'hello', 'en' ) ] );

		expect( widget, 'not to be in edit mode' );

		reactiveRootProps.inEditMode = true;
		widget.$nextTick( function () {
			expect( widget, 'to be in edit mode' );
			done();
		} );
	} );

	it( 'edit mode is false', function ( done ) {
		var widget = newWidgetWithReactiveProps( [ new Lemma( 'hello', 'en' ) ] );

		reactiveRootProps.inEditMode = false;
		widget.$nextTick( function () {
			expect( widget, 'not to be in edit mode' );
			done();
		} );
	} );

	it( 'add a new lemma', function ( done ) {
		var widget = newWidgetWithAccessibleMethods( [ new Lemma( 'hello', 'en' ) ] );

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
			widget = newWidgetWithAccessibleMethods( [ lemmaToRemove ] );

		expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
		widget.remove( lemmaToRemove );
		widget.$nextTick( function () {
			expect( widget.$el, 'to contain no lemmas' );
			done();
		} );
	} );

	it( 'can carry redundant lemma languages', function ( done ) {
		var widget = newWidgetWithAccessibleMethods( [ new Lemma( 'hello', 'en' ), new Lemma( 'world', 'en' ) ] );

		widget.$nextTick( function () {
			expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
			expect( widget.$el, 'to contain lemma', new Lemma( 'world', 'en' ) );
			done();
		} );
	} );

	it( 'detects redundant lemma language to mark the individual languages', function () {
		var widget = newWidgetWithAccessibleMethods( [ new Lemma( 'hello', 'en' ), new Lemma( 'world', 'en' ) ] );

		expect( widget.isRedundantLanguage( 'en' ), 'to be true' );
		expect( widget.isRedundantLanguage( 'fr' ), 'to be false' );
	} );

	it( 'detects redundant lemma languages to mark the widget', function () {
		var widget = newWidgetWithAccessibleMethods( [ new Lemma( 'hello', 'en' ), new Lemma( 'world', 'en' ) ] );

		expect( widget.hasRedundantLanguage, 'to be true' );
	} );

	it( 'marks-up the lemma term with the lemma language', function ( done ) {
		var widget = newWidgetWithAccessibleMethods( [ new Lemma( 'colour', 'en-GB' ) ] );

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

		var no = expect.flags.not ? ' no ' : ' ';
		expect( widget.$el, 'to contain' + no + 'elements matching', 'input' );
	} );

	function newWidgetWithAccessibleMethods( lemmas ) {
		setReactiveProps( lemmas )
		return Vue.createApp(
			getWidget(),
			reactiveRootProps
		).mount( document.createElement( 'div' ) );
	}

	function newWidgetWithReactiveProps( lemmas ) {
		setReactiveProps( lemmas );
		return Vue.createApp(
			{
				render: function () {
					return Vue.h(
						getWidget(),
						reactiveRootProps
					);
				}
			}
		).mount( document.createElement( 'div' ) );
	}

	function getWidget() {
		return newLemmaWidget( getTemplate( 'resources/templates/lemma.vue.html' ), {
			get: function ( key ) {
				return key;
			}
		} );
	}

	function setReactiveProps( lemmas ) {
		reactiveRootProps = Vue.reactive( {
			lemmas: new LemmaList( lemmas ),
			inEditMode: false,
			isSaving: false
		} );
	}
} );
