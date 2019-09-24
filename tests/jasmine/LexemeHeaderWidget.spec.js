/**
 * @license GPL-2.0-or-later
 */
describe( 'wikibase.lexeme.widgets.LexemeHeader', function () {
	var sinon = require( 'sinon' ),
		expect = require( 'unexpected' ).clone(),
		_ = require( 'lodash' );
	expect.installPlugin( require( 'unexpected-dom' ) );
	expect.installPlugin( require( 'unexpected-sinon' ) );

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

	xit( 'save lemma list', function ( done ) {
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

	xit( 'save lemma list with error', function ( done ) {
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

	it( 'shows save button disabled without changes', function ( done ) {
		var widget = newWidget( { lemmas: [] } );
		widget.edit();
		widget.$nextTick( function () {
			expect( widget.$el.querySelector( '.lemma-widget_save' ), 'to have attributes', { disabled: 'disabled' } );
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
			expect( widget.$el.querySelector( '.lemma-widget_save' ), 'to have attributes', { disabled: 'disabled' } );
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
			Vue.set( widget, 'hasRedundantLemmaLanguage', false );
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
			Vue.set( widget, 'hasRedundantLemmaLanguage', false );
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
			Vue.set( widget, 'hasRedundantLemmaLanguage', false );
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
			Vue.set( widget, 'hasRedundantLemmaLanguage', true );
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
		var element = document.createElement( 'div' ),
			options = newLexemeHeader(
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
			);

		return new Vue( _.merge( options, mergeOptions ) ).$mount();
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
	/* eslint-disable no-tabs */
	function getTemplate() {
		return '<div>'
			+ '<div id="wb-lexeme-header" class="wb-lexeme-header">'
			+ '<div class="wb-lexeme-header_id">({{id}})</div><!-- TODO: i18n parentheses -->'
			+ '<div class="wb-lexeme-header_lemma-widget">'
			+ '<lemma-widget '
			+ ':lemmas="lemmas" '
			+ ':inEditMode="inEditMode" '
			+ ':isSaving="isSaving" '
			+ '@hasRedundantLanguage="hasRedundantLemmaLanguage = $event" '
			+ 'ref="lemmas"></lemma-widget>'
			+ '</div>'
			+ '<div class="lemma-widget_controls" v-if="isInitialized" >'
			+ '<button type="button" class="lemma-widget_edit" v-if="!inEditMode" '
			+ ' :disabled="isSaving" v-on:click="edit">{{\'wikibase-edit\'|message}}</button>'
			+ '<button type="button" class="lemma-widget_save" v-if="inEditMode" '
			+ ' :disabled="isUnsaveable" v-on:click="save">{{\'wikibase-save\'|message}}</button>'
			+ '<button type="button" class="lemma-widget_cancel" v-if="inEditMode" '
			+ ' :disabled="isSaving"  v-on:click="cancel">{{\'wikibase-cancel\'|message}}</button>'
			+ '</div>'
			+ '</div>'
			+ '<language-and-category-widget '
			+ '	:language.sync="language"'
			+ '	:lexicalCategory.sync="lexicalCategory"'
			+ '	:inEditMode="inEditMode"'
			+ '	:isSaving="isSaving"'
			+ '	ref="languageAndLexicalCategory">'
			+ '</language-and-category-widget>'
			+ '</div>';

	}
	/* eslint-enable */

} );
