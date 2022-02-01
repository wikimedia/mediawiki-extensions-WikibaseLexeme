describe( 'GlossWidget', function () {
	var getTemplate = require('./helpers/template-loader');
	var sinon = require( 'sinon' );

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
			return {
				text: function() {
					return key;
				}
			};
		}
	};

	var selector = {
		gloss: '.wikibase-lexeme-sense-gloss',
		glossValueCell: '.wikibase-lexeme-sense-gloss-value-cell',
		glossValue: '.wikibase-lexeme-sense-gloss-value',
		glossLanguage: '.wikibase-lexeme-sense-gloss-language'
	};

	global.wikibase = { // eslint-disable-line no-restricted-globals
		getLanguageNameByCode: function () {
			// this is tested in Wikibase/view/tests/qunit/wikibase/wikibase.getLanguageNameByCode.tests.js
			return 'English';
		},
	};

	var getDirectionality = function ( languageCode ) {
		'use strict';
		return languageCode + '-dir';
	};

	var expect = require( 'unexpected' ).clone();
	expect.installPlugin( require( 'unexpected-dom' ) );
	var GlossWidget = require( './../../resources/widgets/GlossWidget.js' );

	var sandbox;
	var mockLanguageSuggester = {
		setSelectedValue: function () {},
	};

	beforeEach( function () {
		sandbox = sinon.createSandbox();
	} );

	afterEach( function () {
		sandbox.restore();
	} );

	it(
		'create with no glosses - when switched to edit mode empty gloss is added',
		function () {
			$.fn.languagesuggester = sinon.stub(); // pretend the languagesuggester widget exists
			sandbox.stub( $.prototype, 'data' ).returns( mockLanguageSuggester );
			var widget = newWidget( [] );
			var emptyGloss = { language: '', value: '' };

			widget.edit();

			expect( widget.glosses[ 0 ], 'to equal', emptyGloss );
		}
	);

	it( 'switch to edit mode', function ( done ) {
		$.fn.languagesuggester = sinon.stub(); // pretend the languagesuggester widget exists
		sandbox.stub( $.prototype, 'data' ).returns( mockLanguageSuggester );
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
		$.fn.languagesuggester = sinon.stub(); // pretend the languagesuggester widget exists
		sandbox.stub( $.prototype, 'data' ).returns( mockLanguageSuggester );
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

		var widget = GlossWidget.newGlossWidget(
			messages,
			getTemplate('resources/templates/glossWidget.vue.html'),
			glosses,
			function () {
			},
			getDirectionality
		);
		return Vue.createApp( widget )
			.mount( document.createElement( 'div' ) );
	}
} );
