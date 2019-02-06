describe( 'wikibase.lexeme.widgets.GlossWidget', function () {
	global.$ = require( 'jquery' ); // eslint-disable-line no-restricted-globals
	global.mw = { // eslint-disable-line no-restricted-globals
		config: {
			get: function ( key ) {
				switch ( key ) {
				case 'wbRepo':
					return {
						url: 'http://localhost',
						scriptPath: 'w/'
					};
				default:
					throw new Error( 'unknown config key: ' + key );
				}
			}
		},
		message: function ( key ) {
			return key;
		}
	};

	var selector = {
		gloss: '.wikibase-lexeme-sense-gloss',
		glossValueCell: '.wikibase-lexeme-sense-gloss-value-cell',
		glossValue: '.wikibase-lexeme-sense-gloss-value',
		glossLanguage: '.wikibase-lexeme-sense-gloss-language'
	};

	function WikibaseContentLanguages() {}
	WikibaseContentLanguages.prototype.getAll = function () {
		return [ 'en' ];
	};
	WikibaseContentLanguages.prototype.getName = function () {
		return 'English';
	};

	global.wikibase = { // eslint-disable-line no-restricted-globals
		getLanguageNameByCode: function () {
			// this is tested in Wikibase/view/tests/qunit/wikibase/wikibase.getLanguageNameByCode.tests.js
			return 'English';
		},
		WikibaseContentLanguages: WikibaseContentLanguages
	};

	var getDirectionality = function ( languageCode ) {
		'use strict';
		return languageCode + '-dir';
	};

	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );
	var Vue = global.Vue = require( 'vue/dist/vue.js' ); // eslint-disable-line no-restricted-globals
	var GlossWidget = require( 'wikibase.lexeme.widgets.GlossWidget' );

	it(
		'create with no glosses - when switched to edit mode empty gloss is added',
		function () {
			var widget = newWidget( [] );
			var emptyGloss = { language: '', value: '' };

			widget.edit();

			expect( widget.glosses[ 0 ], 'to equal', emptyGloss );
		}
	);

	it( 'switch to edit mode', function ( done ) {
		var widget = newWidget( [ { language: 'en', value: 'gloss in english' } ] );

		assertWidget( widget ).when( 'created' ).dom.hasNoInputFields();

		widget.edit();
		widget.$nextTick( function () {
			assertWidget( widget ).when( 'switched to edit mode' ).isInEditMode();
			assertWidget( widget ).when( 'switched to edit mode' ).dom.hasAtLeastOneInputField();
			expect( widget.$el, 'to contain elements matching', 'input' );
			done();
		} );
	} );

	it( 'initialize widget with one gloss', function () {
		var widget = newWidget( [ { language: 'en', value: 'gloss in english' } ] );

		assertWidget( widget ).when( 'created' ).dom.containsGloss(
			'gloss in english',
			'English'
		);
	} );

	it( 'stop editing', function ( done ) {
		var widget = newWidget( [ { language: 'en', value: 'gloss in english' } ] );

		widget.edit();
		widget.stopEditing();

		widget.$nextTick( function () {
			assertWidget( widget ).when( 'canceled the edit mode' ).isNotInEditMode();
			assertWidget( widget ).when( 'canceled the edit mode' )
				.dom.hasNoInputFields();
			done();
		} );
	} );

	it( 'add a new gloss', function ( done ) {
		var widget = newWidget( [ { language: 'en', value: 'gloss in english' } ] );

		assertWidget( widget ).when( 'created' ).dom.containsGloss(
			'gloss in english',
			'English'
		);
		widget.edit();
		widget.add();
		widget.$nextTick( function () {
			assertWidget( widget ).when( 'addition triggered' )
				.dom.containsInputsWithGloss( 'gloss in english', 'en' );
			assertWidget( widget ).when( 'addition triggered' )
				.dom.containsInputsWithGloss( '', '' );
			done();
		} );
	} );

	it( 'remove a gloss', function ( done ) {
		var gloss = { language: 'en', value: 'gloss in english' },
			widget = newWidget( [ gloss ] );

		widget.edit();
		widget.remove( gloss );

		widget.$nextTick( function () {
			assertWidget( widget ).when( 'addition triggered' )
				.dom.doesntContainInputsWithGloss( 'gloss in english', 'en' );
			done();
		} );
	} );
	it( 'removes empty glosses when saved', function () {
		var gloss = { language: 'en', value: 'gloss in english' },
			widget = newWidget( [ gloss ] );

		widget.edit();
		widget.add();
		widget.stopEditing();

		expect( widget.glosses.length, 'to equal', 1 );
	} );

	it( 'trims user input in gloss value', function ( done ) {
		var gloss = { language: 'en', value: 'hello' },
			widget = newWidget( [ gloss ] );

		widget.edit();
		widget.$nextTick( function () {
			setGlossValue( widget, ' \v\t hello \n ' );
			widget.$nextTick( function () {
				expect( widget.glosses[ 0 ].value, 'to equal', 'hello' );
				done();
			} );
		} );
	} );

	function setGlossValue( widget, value ) {
		var inputElm = widget.$el.querySelector( selector.glossValueCell + ' input' );

		inputElm.value = value;
		inputElm.dispatchEvent( new Event( 'input' ) );
	}

	function assertWidget( widget ) {
		'use strict';

		var when = '';

		expect.addAssertion( '<DOMElement> to have trimmed text <string>', function ( expect, subject, value ) {
			expect( subject.textContent.trim(), 'to equal', value );
		} );

		return {
			isInEditMode: function () {
				expect( widget.inEditMode, 'to be true' );

			},
			isNotInEditMode: function () {
				expect( widget.inEditMode, 'to be false' );
			},
			when: function ( text ) {
				when = 'when ' + text + ': ';
				return this;
			},
			dom: {
				hasNoInputFields: function () {
					expect( widget.$el, 'to contain no elements matching', 'input' );
				},
				hasAtLeastOneInputField: function () {
					expect( widget.$el, 'to contain elements matching', 'input' );
				},
				containsGloss: function ( value, language ) {
					var assertGloss = function ( element ) {
						expect( element, 'queried for first', selector.glossValue, 'to have trimmed text', value );
						expect( element, 'queried for first', selector.glossLanguage, 'to have trimmed text', language );
					};

					expect( widget.$el, 'queried for', selector.gloss, 'to have an item satisfying', assertGloss );
				},
				containsInputsWithGloss: function ( value, language ) {
					var found = false;
					widget.$el.querySelectorAll( selector.gloss ).forEach( function ( el ) {
						var valueInput = el.querySelector( selector.glossValueCell + ' input' );
						var languageInput = el.querySelector( selector.glossLanguage + ' input' );

						found = found ||
							valueInput.value.trim() === value &&
							languageInput.value.trim() === language;
					} );

					var message = when + 'DOM contains inputs with gloss having value "' + value +
						'" and language "' + language + '"';
					expect( found, 'to be true' );
				},
				doesntContainInputsWithGloss: function ( value, language ) {
					var found = false;
					widget.$el.querySelectorAll( selector.gloss ).forEach( function ( element ) {
						var glossValue = element.querySelector( selector.glossValue + ' input' ).value;
						var glossLanguage = element.querySelector( selector.glossLanguage + ' input' ).value;
						found = found || glossValue === value && glossLanguage === language;
					} );

					var message = when + 'DOM doesn\'t contain inputs with gloss ' +
						'having value "' + value + '" and language "' + language + '"';

					expect( found, 'to be false' );
				}
			}

		};
	}

	function newWidget( glosses ) {
		'use strict';
		var messages = {
			get: function ( key ) {
				return key;
			}
		};

		return new Vue( GlossWidget.newGlossWidget(
			messages,
			document.createElement( 'div' ),
			getTemplate(),
			glosses,
			function () {
			},
			getDirectionality
		) );
	}

	/* FIXME: duplicated from SensesView.php but out of date because bringing up-to-date breaks the tests
	* i.e. if we actually include the components rather than just using input.
	* For now this is only properly covered by the browser tests
	*/
	function getTemplate() {
		return '<div class="wikibase-lexeme-sense-glosses">\n' +
			'<table class="wikibase-lexeme-sense-glosses-table">\n' +
			'<tbody>\n' +
			'<tr v-for="gloss in glosses" class="wikibase-lexeme-sense-gloss">\n' +
			'<td class="wikibase-lexeme-sense-gloss-language">\n' +
			'<span v-if="!inEditMode">{{gloss.language|languageName}}</span>\n' +
			'<input v-else class="wikibase-lexeme-sense-gloss-language-input"\n' +
			'v-model="gloss.language" >\n' +
			'</td>\n' +
			'<td class="wikibase-lexeme-sense-gloss-value-cell">\n' +
			'<span v-if="!inEditMode" class="wikibase-lexeme-sense-gloss-value"\n' +
			':dir="gloss.language|directionality" :lang="gloss.language">\n' +
			'{{gloss.value}}\n' +
			'</span>\n' +
			'<input v-if="inEditMode" class="wikibase-lexeme-sense-gloss-value-input"\n' +
			':value="gloss.value" @input="gloss.value = $event.target.value.trim()">\n' +
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
} );
