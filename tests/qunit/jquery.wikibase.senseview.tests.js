/**
 * @license GPL-2.0-or-later
 */
( function ( $, wb, QUnit ) {
	'use strict';

	var TEST_LEXMEFORMVIEW_CLASS = 'test_senseview';

	QUnit.module( 'jquery.wikibase.senseview', QUnit.newMwEnvironment( {
		teardown: function () {
			$( '.' + TEST_LEXMEFORMVIEW_CLASS ).remove();
		}
	} ) );

	var newSenseView = function ( options ) {
		var $node = $( '<div/>' ).appendTo( 'body' );
		options = options || {};

		$node.addClass( TEST_LEXMEFORMVIEW_CLASS );

		options.buildStatementGroupListView = function () {};

		return $node.senseview( options || {} ).data( 'senseview' );
	};

	var newSense = function ( id, enGloss ) {
		return new wb.lexeme.datamodel.Sense( id, { en: enGloss } );
	};

	QUnit.test( 'can be created', function ( assert ) {
		var sense = newSense( 'S123', 'foo' );

		assert.ok( newSenseView( { value: sense } ) instanceof $.wikibase.senseview );
	} );

	QUnit.test( 'value can be injected as option.value', function ( assert ) {
		var sense = newSense( 'S123', 'foo' ),
			view = newSenseView( { value: sense } );

		assert.equal( view.value(), sense );
	} );

	QUnit.test( 'value() sets internal value', function ( assert ) {
		var sense1 = newSense( 'S123', 'foo' ),
			sense2 = newSense( 'S234', 'bar' ),
			view = newSenseView( { value: sense2 } );

		view.value( sense2 );
		assert.equal( view.value(), sense2 );
	} );

}( jQuery, wikibase, QUnit ) );
