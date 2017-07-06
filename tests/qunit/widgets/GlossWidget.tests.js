/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit, Vue, Vuex ) {
	'use strict';

	var GlossWidget = require( 'wikibase.lexeme.widgets.GlossWidget' );

	var EMPTY_GLOSS = { language: '', value: '' };

	QUnit.module(
		'wikibase.lexeme.widgets.GlossWidget',
		setUpCustomAssertions(),
		function () {
			QUnit.module( 'widget', function () {
				QUnit.test( 'initialize widget with one gloss', function ( assert ) {
					var widget = newWidget(
						'S1',
						[ { language: 'en', value: 'gloss in english' } ]
					);

					assert.widget( widget ).when( 'created' ).dom.containsGloss(
						'gloss in english',
						'en'
					);
				} );

				QUnit.test(
					'create with no glosses - when switched to edit mode empty gloss is added',
					function ( assert ) {
						var widget = newWidget( 'S1', [] );

						widget.edit();

						assert.deepEqual( widget.glosses[ 0 ], EMPTY_GLOSS );
					}
				);

				QUnit.test( 'switch to edit mode', function ( assert ) {
					var done = assert.async(),
						widget = newWidget(
							'S1',
							[ { language: 'en', value: 'gloss in english' } ]
						);

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
						widget = newWidget(
							'S1',
							[ { language: 'en', value: 'gloss in english' } ]
						);

					widget.edit();
					widget.cancel();
					widget.$nextTick( function () {
						assert.widget( widget ).when( 'canceled the edit mode' ).isNotInEditMode();
						assert.widget( widget ).when( 'canceled the edit mode' )
							.dom.hasNoInputFields();
						done();
					} );
				} );

				QUnit.test( 'add a new gloss', function ( assert ) {
					var done = assert.async(),
						widget = newWidget(
							'S1',
							[ { language: 'en', value: 'gloss in english' } ]
						);

					assert.widget( widget ).when( 'created' ).dom.containsGloss(
						'gloss in english',
						'en'
					);
					widget.edit();
					widget.add();
					widget.$nextTick( function () {
						assert.widget( widget ).when( 'addition triggered' )
							.dom.containsInputsWithGloss( 'gloss in english', 'en' );
						assert.widget( widget ).when( 'addition triggered' )
							.dom.containsInputsWithGloss( '', '' );
						done();
					} );
				} );

				QUnit.test( 'remove a gloss', function ( assert ) {
					var gloss = { language: 'en', value: 'gloss in english' },
						done = assert.async(),
						widget = newWidget(
							'S1',
							[ gloss ]
						);

					widget.edit();
					widget.remove( gloss );

					widget.$nextTick( function () {
						assert.widget( widget ).when( 'addition triggered' )
							.dom.doesntContainInputsWithGloss( 'gloss in english', 'en' );
						done();
					} );
				} );

				QUnit.test( 'save gloss list', function ( assert ) {
					var done = assert.async(),
						glosses = [ { language: 'en', value: 'gloss in english' } ],
						store = newStore( glosses ),
						widget = newWidgetWithStore( 'S1', store ),
						storeSpy = this.stub( store, 'dispatch', function () {
							return $.Deferred().resolve().promise();
						} );

					widget.edit();
					widget.save().then( function () {
						assert.ok( storeSpy.called );
						assert.ok( storeSpy.calledWith( 'save', glosses ) );
						assert.widget( widget ).when( 'saved' ).isNotInEditMode();
						done();
					} );
				} );

				QUnit.test( 'when saving all controls are disabled', function ( assert ) {
					var done = assert.async(),
						glosses = [ { language: 'en', value: 'gloss in english' } ],
						store = newStore( glosses );
					store.commit( 'startSaving' );
					var widget = newWidgetWithStore( 'S1', store );

					widget.edit();
					widget.$nextTick( function () {
						assert.widget( widget ).when( 'saving' ).dom.hasAllControlsDisabled();
						done();
					} );
				} );
			} );

			QUnit.module( 'store', function () {
				QUnit.module( 'mutations', function () {
					var mutations = GlossWidget.newGlossWidgetStore( [] ).mutations;

					QUnit.test( 'startSaving switches the isSaving to true', function ( assert ) {
						var state = { isSaving: false };

						mutations.startSaving( state );

						assert.ok( state.isSaving );
					} );

					QUnit.test( 'finishSaving switches the isSaving to false', function ( assert ) {
						var state = { isSaving: true };

						mutations.finishSaving( state );

						assert.notOk( state.isSaving );
					} );

					QUnit.test( 'updateGlosses sets glosses', function ( assert ) {
						var state = { glosses: [] };

						var newGlosses = [ { language: 'en', value: 'gloss' } ];
						mutations.updateGlosses( state, newGlosses );

						assert.deepEqual( state.glosses, newGlosses );
					} );
				} );

				//TODO: test save action

			} );

		}
	);

	function newWidget( senseId, glosses ) {
		var store = newStore( glosses );
		return newWidgetWithStore( senseId, store );
	}

	function newStore( initialGlosses ) {
		return new Vuex.Store( GlossWidget.newGlossWidgetStore( initialGlosses ) );
	}

	function newWidgetWithStore( senseId, store ) {
		return new Vue( GlossWidget.newGlossWidget(
			document.createElement( 'div' ),
			getTemplate(),
			senseId,
			store
		) );
	}

	function setUpCustomAssertions() {
		return {
			setup: function () {
				QUnit.assert.widget = function assertWidget( widget ) {
					var assert = this,
						when = '',
						selector = {
							gloss: '.wikibase-lexeme-sense-gloss',
							glossValueCell: '.wikibase-lexeme-sense-gloss-value-cell',
							glossValue: '.wikibase-lexeme-sense-gloss-value',
							glossLanguage: '.wikibase-lexeme-sense-gloss-language'
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
							hasAllControlsDisabled: function () {
								var result = true;
								$( widget.$el ).find( 'input, button, select, textarea' ).each(
									function () {
										result = result && $( this ).prop( 'disabled' );
									} );

								assert.ok( result, when + 'DOM has all controls disabled' );
							},
							hasAtLeastOneInputField: function () {
								assert.ok(
									$( widget.$el ).find( 'input' ).length > 0,
									when + 'has at leas one input in DOM '
								);
							},
							containsGloss: function ( value, language ) {
								var found = false;
								$( selector.gloss, widget.$el ).each( function () {
									var $el = $( this );
									var value = $el.find( selector.glossValue ).text();
									var language = $el.find( selector.glossLanguage ).text();
									found = found ||
										value.trim() === value &&
										language.trim() === language;
								} );
								var message = when + 'DOM contains gloss with value "' + value +
									'" and language "' + language + '"';
								return assert.pushResult( {
									result: found,
									actual: found,
									expected: { value: value, language: language },
									message: message,
									negative: false
								} );
							},
							containsInputsWithGloss: function ( value, language ) {
								var found = false;
								$( selector.gloss, widget.$el ).each( function () {
									var $el = $( this );
									var value = $el.find( selector.glossValueCell )
										.find( 'input' ).val();
									var language = $el.find( selector.glossLanguage )
										.find( 'input' ).val();
									found = found ||
										value.trim() === value &&
										language.trim() === language;
								} );
								var message = when + 'DOM contains inputs with gloss having value "' + value +
									'" and language "' + language + '"';
								return assert.pushResult( {
									result: found,
									actual: found,
									expected: { value: value, language: language },
									message: message,
									negative: false
								} );
							},
							doesntContainInputsWithGloss: function ( value, language ) {
								var found = false;
								$( selector.gloss, widget.$el ).each( function () {
									var $el = $( this );
									found = found ||
										$el.children( selector.glossValue ).find( 'input' ).val() === value &&
										$el.children( selector.glossLanguage ).find( 'input' ).val() === language;
								} );
								var message = when + 'DOM doesn\'t contain inputs with gloss ' +
									'having value "' + value + '" and language "' + language + '"';
								return assert.pushResult( {
									result: !found,
									actual: { value: value, language: language },
									expected: { value: value, language: language },
									message: message,
									negative: true
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

	// FIXME: duplicated from SensesView.php until it's reusable
	function getTemplate() {
		return '<div class="wikibase-lexeme-sense-glosses">\n' +
			'<div class="wikibase-lexeme-sense-glosses-list">\n' +
			'<table class="wikibase-lexeme-sense-glosses-table">\n' +
			'<tbody>\n' +
			'<tr v-for="gloss in glosses" class="wikibase-lexeme-sense-gloss">\n' +
			'<td class="wikibase-lexeme-sense-gloss-language">\n' +
			'<span v-if="!inEditMode">{{gloss.language}}</span>\n' +
			'<input v-else class="wikibase-lexeme-sense-gloss-language-input"\n' +
			'v-model="gloss.language" :disabled="isSaving">\n' +
			'</td>\n' +
			'<td class="wikibase-lexeme-sense-gloss-value-cell">\n' +
			'<span v-if="!inEditMode" class="wikibase-lexeme-sense-gloss-value"\n' +
			':dir="gloss.language|directionality" :lang="gloss.language">{{gloss.value}}</span>\n' +
			'<span v-if="!inEditMode" class="wikibase-lexeme-sense-glosses-sense-id">\n' +
			'({{senseId}})\n' +
			'</span>\n' +
			'<input v-if="inEditMode" class="wikibase-lexeme-sense-gloss-value-input"\n' +
			'v-model="gloss.value" :disabled="isSaving">\n' +
			'</td>\n' +
			'<td>\n' +
			'<button v-if="inEditMode"\n' +
			'class="wikibase-lexeme-sense-glosses-control\n' +
			'wikibase-lexeme-sense-glosses-remove"\n' +
			'v-on:click="remove(gloss)" :disabled="isSaving" type="button">\n' +
			'{{\'wikibase-remove\'|message}}\n' +
			'</button>\n' +
			'</td>\n' +
			'</tr>\n' +
			'</tbody>\n' +
			'<tfoot v-if="inEditMode">\n' +
			'<tr>\n' +
			'<td>\n' +
			'</td>\n' +
			'<td>\n' +
			'<button type="button"\n' +
			'class="wikibase-lexeme-sense-glosses-control\n' +
			'wikibase-lexeme-sense-glosses-add"\n' +
			'v-on:click="add" :disabled="isSaving">+ {{\'wikibase-add\'|message}}\n' +
			'</button>\n' +
			'</td>\n' +
			'</tr>\n' +
			'</tfoot>\n' +
			'</table>\n' +
			'</div>\n' +
			'<div class="wikibase-lexeme-sense-glosses-controls">\n' +
			'<button v-if="!inEditMode" type="button"\n' +
			'class="wikibase-lexeme-sense-glosses-control wikibase-lexeme-sense-glosses-edit"\n' +
			'v-on:click="edit" :disabled="isSaving">{{\'wikibase-edit\'|message}}</button>\n' +
			'<button v-if="inEditMode" type="button"\n' +
			'class="wikibase-lexeme-sense-glosses-control wikibase-lexeme-sense-glosses-save"\n' +
			'v-on:click="save" :disabled="isSaving">{{\'wikibase-save\'|message}}</button>\n' +
			'<button v-if="inEditMode" type="button"\n' +
			'class="wikibase-lexeme-sense-glosses-control wikibase-lexeme-sense-glosses-cancel"\n' +
			'v-on:click="cancel" :disabled="isSaving">{{\'wikibase-cancel\'|message}}</button>\n' +
			'</div>\n' +
			'</div>';

	}
}( wikibase, jQuery, QUnit, Vue, Vuex ) );
