/**
 * @license GPL-2.0+
 */
describe( 'wikibase.lexeme.widgets.LemmaWidget', function () {
	var sinon = require( 'sinon' );
	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );

	var Vue = global.Vue = require( 'vue/dist/vue.js' );
	var Vuex = global.Vuex = require( 'vuex/dist/vuex.js' );
	Vue.use( Vuex );

	var newLemmaWidget = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget' );
	var newLemmaWidgetStore = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	it( 'initialize widget with one lemma', function () {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		assertWidget( widget ).dom.containsLemma( 'hello', 'en' );
	} );

	it( 'switch to edit mode', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		assertWidget( widget ).dom.hasNoInputFields();

		widget.edit();
		widget.$nextTick( function () {
			assertWidget( widget ).isInEditMode();
			assertWidget( widget ).dom.hasAtLeastOneInputField();
			done();
		} );
	} );

	it( 'cancel edit mode', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		widget.edit();
		widget.cancel();
		widget.$nextTick( function () {
			assertWidget( widget ).dom.hasNoInputFields();
			done();
		} );
	} );

	it( 'add a new lemma', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		assertWidget( widget ).dom.containsLemma( 'hello', 'en' );
		widget.add();
		widget.$nextTick( function () {
			assertWidget( widget ).dom.containsLemma( 'hello', 'en' );
			assertWidget( widget ).dom.containsLemma( '', '' );
			done();
		} );
	} );

	it( 'remove a lemma', function ( done ) {
		var lemmaToRemove = new Lemma( 'hello', 'en' ),
			widget = newWidget( [ lemmaToRemove ] );

		assertWidget( widget ).dom.containsLemma( 'hello', 'en' );
		widget.remove( lemmaToRemove );
		widget.$nextTick( function () {
			assertWidget( widget ).dom.containsNoLemmas();
			done();
		} );
	} );

	it( 'save lemma list', function ( done ) {
		var lemmas = [ new Lemma( 'hello', 'en' ) ],
			store = newStore( lemmas ),
			widget = newWidgetWithStore( store ),
			storeSpy = sinon.stub( store, 'dispatch' ).callsFake( function () {
				return Promise.resolve();
			} );

		widget.edit();
		widget.save().then( function () {
			expect( storeSpy.called, 'to be true' );
			expect( storeSpy.calledWith( 'save', lemmas ), 'to be true' );
			assertWidget( widget ).isNotInEditMode();
			done();
		} );
	} );

	var messages = { get: function ( key ) { return key; } };
	function newWidget( initialLemmas ) {
		return newWidgetWithStore( newStore( initialLemmas ), messages );
	}

	function newStore( initialLemmas ) {
		return new Vuex.Store( newLemmaWidgetStore( {}, initialLemmas, '', 0 ) );
	}

	function newWidgetWithStore( store ) {
		var element = document.createElement( 'div' );

		return new Vue( newLemmaWidget( store, element, getTemplate(), messages ) );
	}

	function assertWidget( widget ) {
		var selector = {
			lemma: '.lemma-widget_lemma',
			lemmaValue: '.lemma-widget_lemma-value',
			lemmaLanguage: '.lemma-widget_lemma-language'
		};

		return {
			isInEditMode: function () {
				expect( widget.inEditMode, 'to be true' );
			},
			isNotInEditMode: function () {
				expect( widget.inEditMode, 'to be false' );
			},
			dom: {
				hasNoInputFields: function () {
					expect( widget.$el, 'to contain no elements matching', 'input' );
				},
				hasAtLeastOneInputField: function () {
					expect( widget.$el, 'to contain elements matching', 'input' );
				},
				containsNoLemmas: function () {
					expect( widget.$el, 'to contain no elements matching', selector.lemma );
				},
				containsLemma: function ( value, language ) {
					var found = false;
					widget.$el.querySelectorAll( selector.lemma ).forEach( function ( element ) {
						var lemmaValue = element.querySelector( selector.lemmaValue ).textContent,
							lemmaLanguage = element.querySelector( selector.lemmaLanguage ).textContent;
						found = found || lemmaValue === value && lemmaLanguage === language;
					} );

					expect( found, 'to be true' );
				}
			}

		};
	}

	// FIXME: duplicated from LexemeView.php until it's reusable
	function getTemplate() {
		return '<div class="lemma-widget">\n    <ul v-if="!inEditMode" class="lemma-widget_lemma-list">\n        <li v-for="lemma in lemmas" class="lemma-widget_lemma">\n            <span class="lemma-widget_lemma-value">{{lemma.value}}</span>\n            <span class="lemma-widget_lemma-language">{{lemma.language}}</span>\n        </li>\n    </ul>\n    <div v-else>\n        <div class="lemma-widget_edit-area">\n            <ul class="lemma-widget_lemma-list">\n                <li v-for="lemma in lemmas" class="lemma-widget_lemma-edit-box">\n                    <input size="1" class="lemma-widget_lemma-value-input" \n                        v-model="lemma.value" :disabled="isSaving">\n                    <input size="1" class="lemma-widget_lemma-language-input" \n                        v-model="lemma.language" :disabled="isSaving">\n                    <button class="lemma-widget_lemma-remove" v-on:click="remove(lemma)" \n                        :disabled="isSaving" :title="\'wikibase-remove\'|message">\n                        &times;\n                    </button>\n                </li>\n                <li>\n                    <button type="button" class="lemma-widget_add" v-on:click="add" \n                        :disabled="isSaving" :title="\'wikibase-add\'|message">+</button>\n                </li>\n            </ul>\n        </div>\n    </div>\n    <div class="lemma-widget_controls">\n        <button type="button" class="lemma-widget_control" v-if="!inEditMode" \n            :disabled="isSaving" v-on:click="edit">{{\'wikibase-edit\'|message}}</button>\n        <button type="button" class="lemma-widget_control" v-if="inEditMode" \n            :disabled="isSaving" v-on:click="save">{{\'wikibase-save\'|message}}</button>\n        <button type="button" class="lemma-widget_control" v-if="inEditMode" \n            :disabled="isSaving"  v-on:click="cancel">{{\'wikibase-cancel\'|message}}</button>\n    </div>\n</div>';

	}
} );
