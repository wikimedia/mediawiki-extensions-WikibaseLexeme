/**
 * @license GPL-2.0-or-later
 */
describe( 'wikibase.lexeme.widgets.LexemeHeader', function () {
	var sinon = require( 'sinon' ),
		expect = require( 'unexpected' ).clone(),
		_ = require( 'lodash' );
	expect.installPlugin( require( 'unexpected-dom' ) );
	expect.installPlugin( require( 'unexpected-sinon' ) );

	var getTemplate = require('./helpers/template-loader');

	var newLexemeHeader = require( './../../resources/widgets/LexemeHeader.newLexemeHeader.js' );
	var newLexemeHeaderStore = require( './../../resources/widgets/LexemeHeader.newLexemeHeaderStore.js' );
	var Lemma = require( './../../resources/datamodel/Lemma.js' );
	var LemmaList = require( './../../resources/datatransfer/LemmaList.js' );

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
				store.commit( 'updateLemmas', { en: { value: 'hi', language: 'en' } } );
				return Promise.resolve();
			} );

		widget.edit();
		widget.save().then( function () {
			expect( storeSpy, 'to have a call satisfying', [ 'save', lexeme ] );
			expect( widget, 'not to be in edit mode' );
			expect( widget.$refs.lemmas.lemmas, 'to equal', new LemmaList( [ new Lemma( 'hi', 'en' ) ] ) );
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

		expect( widget.$refs.lemmas.lemmas, 'to equal', new LemmaList( lemmas ) );
	} );

	it( 'passes language and lexical category to LanguageAndLexicalCategoryWidget', function () {
		var language = 'Q123',
			lexicalCategory = 'Q234',
			widget = newWidget( { lemmas: [], language: language, lexicalCategory: lexicalCategory } );

		expect( widget.$refs.languageAndLexicalCategory.language, 'to equal', language );
		expect( widget.$refs.languageAndLexicalCategory.lexicalCategory, 'to equal', lexicalCategory );
	} );

	it( 'updates language and lexical category on save', function ( done ) {
		var language = 'Q123',
			lexicalCategory = 'Q234',
			lexeme = { lemmas: [ new Lemma( 'hi', 'en' ) ], language: language, lexicalCategory: lexicalCategory },
			store = newStore( lexeme ),
			widget = newWidgetWithStore( store ),
			storeSpy = sinon.stub( store, 'dispatch' ).callsFake( function () {
				store.commit( 'updateLanguage', { id: language + '0', link: '' } );
				store.commit( 'updateLexicalCategory', { id: lexicalCategory + '0', link: '' } );
				return Promise.resolve();
			} );

		widget.edit();
		widget.save().then( function () {
			expect( widget.$refs.languageAndLexicalCategory.language, 'to equal', language + '0' );
			expect( widget.$refs.languageAndLexicalCategory.lexicalCategory, 'to equal', lexicalCategory + '0' );
			done();
		} );
	} );

	it( 'shows save button disabled without changes', function ( done ) {
		var widget = newWidget( { lemmas: [] } );
		widget.edit();
		widget.$nextTick( function () {
			expect( widget.$el.querySelector( '.lemma-widget_save' ), 'to have attributes', { disabled: true } );
			done();
		} );
	} );

	it( 'shows save button disabled when unsaveable', function ( done ) {
		var widget = newWidget( { lemmas: [] }, {
			computed: {
				isUnsaveable: function () {
					return true;
				}
			}
		} );
		widget.edit();
		widget.$nextTick( function () {
			expect( widget.$el.querySelector( '.lemma-widget_save' ), 'to have attributes', { disabled: true } );
			done();
		} );
	} );

	it( 'shows save button enabled when not unsaveable', function ( done ) {
		var widget = newWidget( { lemmas: [] }, {
			computed: {
				isUnsaveable: function () {
					return false;
				}
			}
		} );
		widget.edit();
		widget.$nextTick( function () {
			expect( widget.$el.querySelector( '.lemma-widget_save' ).getAttribute( 'disabled' ), 'to equal', null );
			done();
		} );
	} );

	it( 'binds to lemma-widget hasRedundantLanguage event', function () {
		var widget = newWidget( { lemmas: [] } );

		widget.$refs.lemmas.$emit( 'hasRedundantLanguage', true );
		expect( widget.hasRedundantLemmaLanguage, 'to be true' );
	} );

	describe( 'isUnsaveable', function () {
		it( 'returns false by default', function () {
			var widget = newWidget( { lemmas: [] }, {
				computed: {
					hasChanges: function () {
						return true;
					}
				}
			} );
			widget.hasRedundantLemmaLanguage = false;
			expect( widget.isUnsaveable, 'to be false' );
		} );

		it( 'returns true when there are no changes', function () {
			var widget = newWidget( { lemmas: [] }, {
				computed: {
					hasChanges: function () {
						return false;
					}
				}
			} );
			widget.hasRedundantLemmaLanguage = false;
			expect( widget.isUnsaveable, 'to be true' );
		} );

		it( 'returns true when there are changes but saving is ongoing', function () {
			var widget = newWidget( { lemmas: [] }, {
				computed: {
					isSaving: function () {
						return true;
					},
					hasChanges: function () {
						return true;
					}
				}
			} );
			widget.hasRedundantLemmaLanguage = false;
			expect( widget.isUnsaveable, 'to be true' );
		} );

		it( 'returns true when there are changes but also lemmas with redundant languages', function () {
			var widget = newWidget( { lemmas: [] }, {
				computed: {
					hasChanges: function () {
						return true;
					}
				}
			} );
			widget.hasRedundantLemmaLanguage = true;
			expect( widget.isUnsaveable, 'to be true' );
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

	/**
	 * @param {Object} lexeme
	 * @param {Object} mergeOptions Additional Vue options to apply to component, e.g. to mock watched properties
	 * @return {Vue}
	 */
	function newWidget( lexeme, mergeOptions ) {
		return newWidgetWithStore( newStore( lexeme ), mergeOptions );
	}

	function newStore( lexeme ) {
		return new Vuex.Store( newLexemeHeaderStore( {}, lexeme, 0 ) );
	}

	/**
	 * @param {Vuex.Store} store
	 * @param {Object} mergeOptions Additional Vue options to apply to component, e.g. to mock watched properties
	 * @return {Vue}
	 */
	function newWidgetWithStore( store, mergeOptions ) {
		var template = getTemplate('resources/templates/lexemeHeader.vue.html').replace('%saveMessageKey%', 'wikibase-save');
		var options = newLexemeHeader(
				store,
				template,
				getLemmaWidget(),
				getLanguageAndLexicalCategoryWidget(),
				{
					get: function ( key ) {
						return key;
					}
				}
			);

		return Vue.createApp( _.merge( options, mergeOptions ) )
			.use( store )
			.mount( document.createElement( 'div' ) );
	}

	function getLemmaWidget() {
		return {
			name: 'lemma-widget',
			props: [ 'lemmas', 'inEditMode', 'isSaving' ],
			template: '<div></div>'
		};
	}

	function getLanguageAndLexicalCategoryWidget() {
		return {
			props: [ 'language', 'lexicalCategory', 'inEditMode', 'isSaving' ],
			template: '<div></div>'
		};
	}
} );
