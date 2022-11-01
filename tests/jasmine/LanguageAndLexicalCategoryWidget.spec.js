/**
 * @license GPL-2.0-or-later
 */
describe( 'LanguageAndLexicalCategoryWidget', function () {
	global.$ = require( 'jquery' ); // eslint-disable-line no-restricted-globals
	global.mw = { // eslint-disable-line no-restricted-globals
		config: {
			get: function () {
				return '';
			}
		}
	};

	var getTemplate = require('./helpers/template-loader');
	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );

	var newLanguageAndLexicalCategoryWidget = require( './../../resources/widgets/LanguageAndLexicalCategoryWidget.js' );
	var sinon = require( 'sinon' );
	var sandbox;
	beforeEach( function () {
		sandbox = sinon.createSandbox();
	} );
	afterEach( function () {
		sandbox.restore();
	} );

	it( 'shows the language and the lexical category', function () {
		var language = 'Q123',
			lexicalCategory = 'Q234',
			widget = newWidget( language, lexicalCategory );

		expect( widget.$el.textContent, 'to contain', 'Link for ' + language );
		expect( widget.$el.textContent, 'to contain', 'Link for ' + lexicalCategory );

		expect( widget.$el, 'to contain elements matching', '.language-link' );
		expect( widget.$el, 'to contain elements matching', '.lexical-category-link' );
	} );

	it( 'switches to edit mode and back', async function () {
		var mockEntitySelector = {
			selectedEntity: function () {
			},
			destroy: sinon.stub()
		};
		sandbox.stub( $.prototype, 'data' ).returns( mockEntitySelector )
		$.fn.entityselector = sinon.stub(); // pretend the entityselector widget exists

		var widget = newWidget( 'Q123', 'Q234' );

		expect( widget, 'not to be in edit mode' );

		widget.inEditMode = true;
		await widget.$nextTick();
		expect( widget, 'to be in edit mode' );

		widget.inEditMode = false;
		await widget.$nextTick();
		expect( widget, 'not to be in edit mode' );
	} );

	expect.addAssertion( '<object> [not] to be in edit mode', function ( expect, widget ) {
		expect.errorMode = 'nested';

		expect( widget.inEditMode, '[not] to be true' );
		var no = expect.flags.not ? ' no ' : ' ';
		expect( widget.$el, 'to contain' + no + 'elements matching', 'input' );
	} );

	function newWidget( language, lexicalCategory ) {
		var template = getTemplate('resources/templates/languageAndLexicalCategoryWidget.vue.html');
		var mockApi = {
			formatValue: function () { return Promise.resolve( { result: '' } ); },
		};
		var LanguageAndLexicalCategoryWidget = Vue.extend( newLanguageAndLexicalCategoryWidget( template, mockApi, {
			get: function ( key ) {
				return key;
			}
		} ) );

		return new LanguageAndLexicalCategoryWidget( {
			store: {
				state: {
					languageLink: '<a href="#" class="language-link">Link for ' + language + '</a>',
					lexicalCategoryLink: '<a href="#" class="lexical-category-link">Link for ' + lexicalCategory + '</a>'
				}
			},
			propsData: {
				language: language,
				lexicalCategory: lexicalCategory,
				inEditMode: false,
				isSaving: false
			}
		} ).$mount();
	}
} );
