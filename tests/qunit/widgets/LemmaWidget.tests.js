/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit, Vue, Vuex ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.widgets.LemmaWidget', setUpCustomAssertions() );

	var newLemmaWidget = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget' );
	var newLemmaWidgetStore = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	QUnit.test( 'initialize widget with one lemma', function ( assert ) {
		var widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		assert.widget( widget ).when( 'created' ).dom.containsLemma( 'hello', 'en' );
	} );

	QUnit.test( 'switch to edit mode', function ( assert ) {
		var done = assert.async(),
			widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		assert.widget( widget ).when( 'created' ).dom.hasNoInputFields();

		widget.edit();
		widget.$nextTick( function () {
			assert.widget( widget ).when( 'switched to edit mode' ).isInEditMode();
			assert.widget( widget ).when( 'switched to edit mode' ).dom.hasAtLeastOneInputField();
			done();
		} );
	} );

	QUnit.test( 'cancel edit mode', function ( assert ) {
		var done = assert.async(),
			widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		widget.edit();
		widget.cancel();
		widget.$nextTick( function () {
			assert.widget( widget ).when( 'canceled the edit mode' ).dom.hasNoInputFields();
			done();
		} );
	} );

	QUnit.test( 'add a new lemma', function ( assert ) {
		var done = assert.async(),
			widget = newWidget( [ new Lemma( 'hello', 'en' ) ] );

		assert.widget( widget ).when( 'created' ).dom.containsLemma( 'hello', 'en' );
		widget.add();
		widget.$nextTick( function () {
			assert.widget( widget ).when( 'addition triggered' ).dom.containsLemma( 'hello', 'en' );
			assert.widget( widget ).when( 'addition triggered' ).dom.containsLemma( '', '' );
			done();
		} );
	} );

	QUnit.test( 'remove a lemma', function ( assert ) {
		var done = assert.async(),
			lemmaToRemove = new Lemma( 'hello', 'en' ),
			widget = newWidget( [ lemmaToRemove ] );

		assert.widget( widget ).when( 'created' ).dom.containsLemma( 'hello', 'en' );
		widget.remove( lemmaToRemove );
		widget.$nextTick( function () {
			assert.widget( widget ).when( 'lemma removed' ).dom.containsNoLemmas();
			done();
		} );
	} );

	QUnit.test( 'save lemma list', function ( assert ) {
		var done = assert.async(),
			lemmas = [ new Lemma( 'hello', 'en' ) ],
			store = newStore( lemmas ),
			widget = newWidgetWithStore( store ),
			storeSpy = this.stub( store, 'dispatch', function () {
				return $.Deferred().resolve().promise();
			} );

		widget.edit();
		widget.save().then( function () {
			assert.ok( storeSpy.called );
			assert.ok( storeSpy.calledWith( 'save', lemmas ) );
			assert.widget( widget ).when( 'saved' ).isNotInEditMode();
			done();
		} );
	} );

	function newWidget( initialLemmas ) {
		return newWidgetWithStore( newStore( initialLemmas ) );
	}

	function newStore( initialLemmas ) {
		return new Vuex.Store( newLemmaWidgetStore( {}, initialLemmas, '', 0 ) );
	}

	function newWidgetWithStore( store ) {
		var element = document.createElement( 'div' );

		return new Vue( newLemmaWidget( store, element, getTemplate() ) );
	}

	function setUpCustomAssertions() {
		return {
			setup: function () {
				QUnit.assert.widget = function assertWidget( widget ) {
					var assert = this,
						when = '',
						selector = {
							lemma: '.lemma-widget_lemma',
							lemmaValue: '.lemma-widget_lemma-value',
							lemmaLanguage: '.lemma-widget_lemma-language'
						};

					return {
						isInEditMode: function () {
							assert.ok( widget.inEditMode, when + 'is in edit mode' );
						},
						isNotInEditMode: function () {
							assert.notOk( widget.inEditMode, when + 'is not in edit mode' );
						},
						when: function ( text ) {
							when = 'when ' + text + ': ';
							return this;
						},
						dom: {
							hasNoInputFields: function () {
								assert.equal(
									$( widget.$el ).find( 'input' ).length,
									0,
									when + 'DOM has no input fields'
								);
							},
							hasAtLeastOneInputField: function () {
								assert.ok(
									$( widget.$el ).find( 'input' ).length > 0,
									when + 'has at leas one input in DOM '
								);
							},
							containsNoLemmas: function () {
								assert.equal(
									$( selector.lemma, widget.$el ).length,
									0,
									when + 'DOM contains no lemmas'
								);
							},
							containsLemma: function ( value, language ) {
								var found = false;
								$( selector.lemma, widget.$el ).each( function () {
									var $el = $( this );
									found = found ||
										$el.children( selector.lemmaValue ).text() === value &&
										$el.children( selector.lemmaLanguage ).text() === language;
								} );
								var message = when + 'DOM contains lemma with value "' + value +
									'" and language "' + language + '"';
								return assert.pushResult( {
									result: found,
									actual: found,
									expected: { value: value, language: language },
									message: message,
									negative: false
								} );
							}
						}

					};

				};
			},
			teardown: function () {
				delete QUnit.assert.widget;
			}
		};
	}

	// FIXME: duplicated from LexemeView.php until it's reusable
	function getTemplate() {
		return '<div class="lemma-widget">\n    <ul v-if="!inEditMode" class="lemma-widget_lemma-list">\n        <li v-for="lemma in lemmas" class="lemma-widget_lemma">\n            <span class="lemma-widget_lemma-value">{{lemma.value}}</span>\n            <span class="lemma-widget_lemma-language">{{lemma.language}}</span>\n        </li>\n    </ul>\n    <div v-else>\n        <div class="lemma-widget_edit-area">\n            <ul class="lemma-widget_lemma-list">\n                <li v-for="lemma in lemmas" class="lemma-widget_lemma-edit-box">\n                    <input size="1" class="lemma-widget_lemma-value-input" \n                        v-model="lemma.value" :disabled="isSaving">\n                    <input size="1" class="lemma-widget_lemma-language-input" \n                        v-model="lemma.language" :disabled="isSaving">\n                    <button class="lemma-widget_lemma-remove" v-on:click="remove(lemma)" \n                        :disabled="isSaving" :title="\'wikibase-remove\'|message">\n                        &times;\n                    </button>\n                </li>\n                <li>\n                    <button type="button" class="lemma-widget_add" v-on:click="add" \n                        :disabled="isSaving" :title="\'wikibase-add\'|message">+</button>\n                </li>\n            </ul>\n        </div>\n    </div>\n    <div class="lemma-widget_controls">\n        <button type="button" class="lemma-widget_control" v-if="!inEditMode" \n            :disabled="isSaving" v-on:click="edit">{{\'wikibase-edit\'|message}}</button>\n        <button type="button" class="lemma-widget_control" v-if="inEditMode" \n            :disabled="isSaving" v-on:click="save">{{\'wikibase-save\'|message}}</button>\n        <button type="button" class="lemma-widget_control" v-if="inEditMode" \n            :disabled="isSaving"  v-on:click="cancel">{{\'wikibase-cancel\'|message}}</button>\n    </div>\n</div>';

	}
}( wikibase, jQuery, QUnit, Vue, Vuex ) );
