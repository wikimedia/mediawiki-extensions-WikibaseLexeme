/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit, Vue, Vuex ) {
	'use strict';

	var GlossWidget = require( 'wikibase.lexeme.widgets.GlossWidget' );

	QUnit.module(
		'wikibase.lexeme.widgets.GlossWidget',
		setUpCustomAssertions(),
		function () {
			QUnit.module( 'widget', function () {
				QUnit.test( 'initialize widget with one gloss', function ( assert ) {
					var widget = newWidget( [ { language: 'en', value: 'gloss in english' } ] );

					assert.widget( widget ).when( 'created' ).dom.containsGloss(
						'gloss in english',
						'en'
					);
				} );

				QUnit.test(
					'create with no glosses - when switched to edit mode empty gloss is added',
					function ( assert ) {
						var widget = newWidget( [] );
						var emptyGloss = { language: '', value: '' };

						widget.edit();

						assert.deepEqual( widget.glosses[ 0 ], emptyGloss );
					}
				);

				QUnit.test( 'switch to edit mode', function ( assert ) {
					var done = assert.async(),
						widget = newWidget( [ { language: 'en', value: 'gloss in english' } ] );

					assert.widget( widget ).when( 'created' ).dom.hasNoInputFields();

					widget.edit();
					widget.$nextTick( function () {
						assert.widget( widget ).when( 'switched to edit mode' ).isInEditMode();
						assert.widget( widget ).when( 'switched to edit mode' ).dom.hasAtLeastOneInputField();
						done();
					} );
				} );

				QUnit.test( 'stop editing', function ( assert ) {
					var done = assert.async(),
						widget = newWidget( [ { language: 'en', value: 'gloss in english' } ] );

					widget.edit();
					widget.stopEditing();

					widget.$nextTick( function () {
						assert.widget( widget ).when( 'canceled the edit mode' ).isNotInEditMode();
						assert.widget( widget ).when( 'canceled the edit mode' )
							.dom.hasNoInputFields();
						done();
					} );
				} );

				QUnit.test( 'add a new gloss', function ( assert ) {
					var done = assert.async(),
						widget = newWidget( [ { language: 'en', value: 'gloss in english' } ] );

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
						widget = newWidget( [ gloss ] );

					widget.edit();
					widget.remove( gloss );

					widget.$nextTick( function () {
						assert.widget( widget ).when( 'addition triggered' )
							.dom.doesntContainInputsWithGloss( 'gloss in english', 'en' );
						done();
					} );
				} );
			} );
		}
	);

	function newWidget( glosses ) {
		return new Vue( GlossWidget.newGlossWidget(
			document.createElement( 'div' ),
			getTemplate(),
			glosses,
			function () {}
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
							hasAtLeastOneInputField: function () {
								assert.ok(
									$( widget.$el ).find( 'input' ).length > 0,
									when + 'has at least one input in DOM '
								);
							},
							containsGloss: function ( value, language ) {
								var found = false;
								$( selector.gloss, widget.$el ).each( function () {
									var $el = $( this );
									var domValue = $el.find( selector.glossValue ).text();
									var domLanguage = $el.find( selector.glossLanguage ).text();
									found = found ||
										domValue.trim() === value &&
										domLanguage.trim() === language;
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
									var domValue = $el.find( selector.glossValueCell )
										.find( 'input' ).val();
									var domLanguage = $el.find( selector.glossLanguage )
										.find( 'input' ).val();
									found = found ||
										domValue.trim() === value &&
										domLanguage.trim() === language;
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
			'<table class="wikibase-lexeme-sense-glosses-table">\n' +
			'<tbody>\n' +
			'<tr v-for="gloss in glosses" class="wikibase-lexeme-sense-gloss">\n' +
			'<td class="wikibase-lexeme-sense-gloss-language">\n' +
			'<span v-if="!inEditMode">{{gloss.language}}</span>\n' +
			'<input v-else class="wikibase-lexeme-sense-gloss-language-input"\n' +
			'v-model="gloss.language" >\n' +
			'</td>\n' +
			'<td class="wikibase-lexeme-sense-gloss-value-cell">\n' +
			'<span v-if="!inEditMode" class="wikibase-lexeme-sense-gloss-value"\n' +
			':dir="gloss.language|directionality" :lang="gloss.language">\n' +
			'{{gloss.value}}\n' +
			'</span>\n' +
			'<input v-if="inEditMode" class="wikibase-lexeme-sense-gloss-value-input"\n' +
			'v-model="gloss.value" >\n' +
			'</td>\n' +
			'<td>\n' +
			'<button v-if="inEditMode"\n' +
			'class="wikibase-lexeme-sense-glosses-control\n' +
			'wikibase-lexeme-sense-glosses-remove"\n' +
			'v-on:click="remove(gloss)"  type="button">\n' +
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
			'v-on:click="add" >+ {{\'wikibase-add\'|message}}\n' +
			'</button>\n' +
			'</td>\n' +
			'</tr>\n' +
			'</tfoot>\n' +
			'</table>\n' +
			'</div>';

	}
}( wikibase, jQuery, QUnit, Vue, Vuex ) );
