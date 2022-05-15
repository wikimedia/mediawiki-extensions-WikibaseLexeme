/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	var TEST_LEXMEFORMVIEW_CLASS = 'test_senseview';
	var datamodel = require( 'wikibase.datamodel' );
	/** @type {wikibase.datamodel.TermMap} */
	var TermMap = datamodel.TermMap;
	/** @type {wikibase.datamodel.Term} */
	var Term = datamodel.Term;

	QUnit.module( 'jquery.wikibase.senseview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.' + TEST_LEXMEFORMVIEW_CLASS ).remove();
		}
	} ) );

	var newSenseView = function ( options ) {
		var $node = $( '<div>' ).appendTo( 'body' );
		options = options || {};

		// eslint-disable-next-line mediawiki/class-doc
		$node.addClass( TEST_LEXMEFORMVIEW_CLASS );

		options.buildStatementGroupListView = options.buildStatementGroupListView || function () {};

		return $node.senseview( options || {} ).data( 'senseview' );
	};

	var newSense = function ( id, enGloss ) {
		var glosses = new TermMap( { en: new Term( 'en', enGloss ) } );

		return new wb.lexeme.datamodel.Sense( id, glosses );
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
			view = newSenseView( { value: sense1 } );

		view.value( sense2 );
		assert.equal( view.value(), sense2 );
	} );

	QUnit.test( 'Given a value, creates StatementGroupListView with Sense id prefix', function ( assert ) {
		var senseId = 'L1-S123',
			sense = newSense( senseId, 'potatoes' ),
			statementGroupListViewSpy = sinon.spy();

		newSenseView( {
			value: sense,
			buildStatementGroupListView: statementGroupListViewSpy
		} );

		assert.ok( statementGroupListViewSpy.calledWith(
			sense,
			sinon.match.any,
			'S123'
		) );
	} );

	QUnit.test( 'Given a new sense, creates StatementGroupListView with empty prefix', function ( assert ) {
		var sense = new wb.lexeme.datamodel.Sense(), // i.e. default 'undefined' id
			statementGroupListViewSpy = sinon.spy();

		newSenseView( {
			value: sense,
			buildStatementGroupListView: statementGroupListViewSpy
		} );

		assert.ok( statementGroupListViewSpy.calledWith(
			sense,
			sinon.match.any,
			''
		) );
	} );

	QUnit.test( 'sets id after saving sense', function ( assert ) {
		var emptySense = new wikibase.lexeme.datamodel.Sense( '' ),
			statementGroupListViewSpy = sinon.spy(),
			view = newSenseView( {
				value: emptySense,
				buildStatementGroupListView: statementGroupListViewSpy
			} ),
			done = assert.async();

		view.deferredSenseWithId.resolve( newSense( 'L321-S123', 'meow' ) );

		view.deferredSenseWithId.promise().then( function () {
			assert.equal(
				view.element.attr( 'id' ),
				'S123'
			);
			assert.ok( statementGroupListViewSpy.calledWith(
				sinon.match.any,
				sinon.match.any,
				'S123'
			) );
			done();
		} );
	} );

}( wikibase ) );
