/**
 * @license GPL-2.0+
 */
describe( 'wikibase.lexeme.widgets.LemmaWidget', function () {
	var sinon = require( 'sinon' );
	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );
	expect.installPlugin( require( 'unexpected-sinon' ) );

	var Vue = global.Vue = require( 'vue/dist/vue.js' );
	var Vuex = global.Vuex = require( 'vuex/dist/vuex.js' );
	Vue.use( Vuex );

	var newLemmaWidget = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget' );
	var newLemmaWidgetStore = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	it( 'initialize widget with one lemma', function () {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		expect( widget.$el, 'to contain lemma', new Lemma( 'hello', 'en' ) );
	} );

	it( 'switches to edit mode', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		expect( widget, 'not to be in edit mode' );

		widget.edit();
		widget.$nextTick( function () {
			expect( widget, 'to be in edit mode' );
			done();
		} );
	} );

	it( 'cancel edit mode', function ( done ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		widget.edit();
		widget.cancel();
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

	it( 'save lemma list', function ( done ) {
		var lemmas = [ new Lemma( 'hello', 'en' ) ],
			store = newStore( lemmas ),
			widget = newWidgetWithStore( store ),
			storeSpy = sinon.stub( store, 'dispatch' ).callsFake( function () {
				return Promise.resolve();
			} );

		widget.edit();
		widget.save().then( function () {
			expect( storeSpy, 'to have a call satisfying', [ 'save', lemmas ] );
			expect( widget, 'not to be in edit mode' );
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

		return new Vue( newLemmaWidget( store, element, getTemplate(), { get: function ( key ) { return key; } } ) );
	}

	var selector = {
		lemma: '.lemma-widget_lemma',
		lemmaValue: '.lemma-widget_lemma-value',
		lemmaLanguage: '.lemma-widget_lemma-language'
	};

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

	// FIXME: duplicated from LexemeView.php until it's reusable
	function getTemplate() {
		return '<div class="lemma-widget">\n    <ul v-if="!inEditMode" class="lemma-widget_lemma-list">\n        <li v-for="lemma in lemmas" class="lemma-widget_lemma">\n            <span class="lemma-widget_lemma-value">{{lemma.value}}</span>\n            <span class="lemma-widget_lemma-language">{{lemma.language}}</span>\n        </li>\n    </ul>\n    <div v-else>\n        <div class="lemma-widget_edit-area">\n            <ul class="lemma-widget_lemma-list">\n                <li v-for="lemma in lemmas" class="lemma-widget_lemma-edit-box">\n                    <input size="1" class="lemma-widget_lemma-value-input" \n                        v-model="lemma.value" :disabled="isSaving">\n                    <input size="1" class="lemma-widget_lemma-language-input" \n                        v-model="lemma.language" :disabled="isSaving">\n                    <button class="lemma-widget_lemma-remove" v-on:click="remove(lemma)" \n                        :disabled="isSaving" :title="\'wikibase-remove\'|message">\n                        &times;\n                    </button>\n                </li>\n                <li>\n                    <button type="button" class="lemma-widget_add" v-on:click="add" \n                        :disabled="isSaving" :title="\'wikibase-add\'|message">+</button>\n                </li>\n            </ul>\n        </div>\n    </div>\n    <div class="lemma-widget_controls">\n        <button type="button" class="lemma-widget_control" v-if="!inEditMode" \n            :disabled="isSaving" v-on:click="edit">{{\'wikibase-edit\'|message}}</button>\n        <button type="button" class="lemma-widget_control" v-if="inEditMode" \n            :disabled="isSaving" v-on:click="save">{{\'wikibase-save\'|message}}</button>\n        <button type="button" class="lemma-widget_control" v-if="inEditMode" \n            :disabled="isSaving"  v-on:click="cancel">{{\'wikibase-cancel\'|message}}</button>\n    </div>\n</div>';

	}

} );
