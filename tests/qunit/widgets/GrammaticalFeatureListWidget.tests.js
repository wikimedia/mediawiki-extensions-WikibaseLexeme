/**
 * @license GPL-2.0-or-later
 */
( function ( QUnit, require, sinon ) {
	'use strict';

	var INTERNAL_DEBOUNCE_TIMEOUT = 150;

	var GrammaticalFeatureListWidget = require( '../../../resources/widgets/GrammaticalFeatureListWidget.js' );

	var dummyLabelFormattingService = {
		getHtml: function ( itemId ) {
			return $.Deferred().resolve( itemId ).promise();
		}
	};

	QUnit.module( 'GrammaticalFeatureListWidget' );

	QUnit.test( 'throws an error if no api provided', function ( assert ) {
		assert.throws( function () {
			new GrammaticalFeatureListWidget( { language: 'en' } );
		} );
	} );

	QUnit.test( 'throws an error if no formatting service provided', function ( assert ) {
		assert.throws( function () {
			new GrammaticalFeatureListWidget( { language: 'en', api: {} } );
		} );
	} );

	QUnit.test( 'throws an error if no language provided', function ( assert ) {
		assert.throws( function () {
			new GrammaticalFeatureListWidget( { api: {} } );
		} );
	} );

	QUnit.test( 'some string entered in the input - calls api with this string as search argument', function ( assert ) {
		var done = assert.async();
		var apiCallback = function () {
			return $.Deferred().resolve( { search: [] } ).promise();
		};
		var api = { get: sinon.spy( apiCallback ) };
		var widget = new GrammaticalFeatureListWidget( { api: api, language: 'en', debounceInterval: 0, labelFormattingService: {} } );

		widget.input.setValue( 'some input value' );

		setTimeout( function () {
			sinon.assert.calledWith(
				api.get,
				{
					action: 'wbsearchentities',
					format: 'json',
					errorformat: 'plaintext',
					language: 'en',
					search: 'some input value',
					type: 'item',
					uselang: 'en'
				}
			);
			done();
		}, 0 );
	} );

	QUnit.test( 'api returns list of results - menu with these results is displayed', function ( assert ) {
		var done = assert.async();
		var api = {
			get: function () {
				var results = [
					{ id: 'Q1', label: 'q1-label', description: 'q1-description' },
					{ id: 'Q2', label: 'q2-label', description: 'q2-description' }
				];

				return $.Deferred().resolve( { search: results } ).promise();
			}
		};

		var widget = new GrammaticalFeatureListWidget( { api: api, language: 'en', debounceInterval: 0, labelFormattingService: {} } );

		widget.input.setValue( 'anything' );

		setTimeout( function () {
			/** @type {OO.ui.MenuOptionWidget[]} */
			var items = widget.menu.getItems();

			assert.equal( items[ 0 ].getData(), 'Q1' );
			assert.ok( items[ 0 ].$element.text().match( 'q1-label' ) );
			assert.ok( items[ 0 ].$element.text().match( 'q1-description' ) );
			assert.equal( items[ 1 ].getData(), 'Q2' );
			assert.ok( items[ 1 ].$element.text().match( 'q2-label' ) );
			assert.ok( items[ 1 ].$element.text().match( 'q2-description' ) );
			done();
		}, INTERNAL_DEBOUNCE_TIMEOUT );
	} );

	QUnit.skip( 'I select item in menu - the item is added to the widget', function ( assert ) {
		var done = assert.async();
		var api = {
			get: function () {
				var results = [
					{ id: 'Q1', label: 'q1-label', description: 'q1-description' }
				];

				return $.Deferred().resolve( { search: results } ).promise();
			}
		};

		var widget = new GrammaticalFeatureListWidget( {
			api: api,
			labelFormattingService: dummyLabelFormattingService,
			language: 'en',
			debounceInterval: 0
		} );

		widget.input.setValue( 'anything' );

		setTimeout( function () {
			var item = widget.menu.getItems()[ 0 ];
			widget.menu.chooseItem( item );

			assert.equal( widget.getValue()[ 0 ], 'Q1' );
			done();
		}, INTERNAL_DEBOUNCE_TIMEOUT );
	} );

	QUnit.test( 'I can preset selected values', function ( assert ) {
		var widget = new GrammaticalFeatureListWidget( {
			api: {},
			labelFormattingService: {},
			language: 'en',
			debounceInterval: 0,
			selected: [ 'Q1' ],
			options: [ { id: 'Q1' } ]
		} );

		assert.deepEqual( widget.getValue(), [ 'Q1' ] );
	} );

	QUnit.test( 'I can preset selected values with labels', function ( assert ) {
		var widget = new GrammaticalFeatureListWidget( {
			api: {},
			labelFormattingService: dummyLabelFormattingService,
			language: 'en',
			debounceInterval: 0,
			options: [ { id: 'Q1', label: 'Q1-label' } ],
			selected: [ 'Q1' ]
		} );

		assert.deepEqual( widget.getValue(), [ 'Q1' ] );
	} );

}( QUnit, require, sinon ) );
