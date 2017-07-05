/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit ) {
	'use strict';

	var RepresentationWidget = require( 'wikibase.lexeme.widgets.RepresentationWidget' );

	var EMPTY_REPRESENTATION = { language: '', value: '' };
	var SOME_REPRESENTATION = { language: 'en', value: 'representation in english' };

	QUnit.module(
		'wikibase.lexeme.widgets.RepresentationWidget',
		function () {
			QUnit.test(
				'widget without representations, start editing - new empty representation is added',
				function ( assert ) {
					var widget = newWidget( [] );

					widget.edit();

					assert.deepEqual( widget.representations, [ EMPTY_REPRESENTATION ] );
				}
			);

			QUnit.test(
				'widget with representation, start editing - only initial representation present',
				function ( assert ) {
					var widget = newWidget( [ SOME_REPRESENTATION ] );

					widget.edit();

					assert.deepEqual(
						widget.representations,
						[ SOME_REPRESENTATION ]
					);
				}
			);

			QUnit.test( 'when created - not in edit mode', function ( assert ) {
				var widget = newWidget( [] );

				assert.notOk( widget.inEditMode );
			} );

			QUnit.test( 'switch to edit mode', function ( assert ) {
				var widget = newWidget( [] );

				widget.edit();

				assert.ok( widget.inEditMode );
			} );

			QUnit.test( 'not in edit mode after stopEditing was called', function ( assert ) {
				var widget = newWidget( [] );

				widget.edit();
				widget.stopEditing();

				assert.notOk( widget.inEditMode );
			} );

			QUnit.test( 'add a representation - empty representation added', function ( assert ) {
				var widget = newWidget(
					[ SOME_REPRESENTATION ]
				);

				widget.edit();
				widget.add();

				assert.deepEqual( widget.representations[ 1 ], EMPTY_REPRESENTATION );
			} );

			QUnit.test( 'remove a representation', function ( assert ) {
				var widget = newWidget(
					[ SOME_REPRESENTATION ]
				);

				widget.edit();
				widget.remove( SOME_REPRESENTATION );

				assert.deepEqual( widget.representations, [] );
			} );

			QUnit.test( 'cannot add representation if not in edit mode', function ( assert ) {
				var widget = newWidget( [] );

				assert.throws( function () {
					widget.add();
				}, Error );
			} );

			QUnit.test( 'cannot remove representation if not in edit mode', function ( assert ) {
				var widget = newWidget(
					[ SOME_REPRESENTATION ]
				);

				assert.throws( function () {
					widget.remove( SOME_REPRESENTATION );
				}, Error );
			} );

		}
	);

	function newWidget( representations ) {
		return RepresentationWidget.create(
			representations,
			document.createElement( 'div' ),
			'<div id="dummy-template"></div>',
			function () {
			}
		);
	}

}( wikibase, jQuery, QUnit ) );
