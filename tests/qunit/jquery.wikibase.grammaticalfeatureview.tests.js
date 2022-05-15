/**
 * @license GPL-2.0-or-later
 */
( function () {
	'use strict';

	var TEST_ELEMENT_CLASS = 'test_grammaticalfeatureview';

	QUnit.module( 'jquery.wikibase.grammaticalfeatureview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.' + TEST_ELEMENT_CLASS ).remove();
		}
	} ) );

	var newGrammaticalFeatureView = function ( options ) {
		options = options || {};
		var $node = $( '<div>' ).appendTo( 'body' );

		// eslint-disable-next-line mediawiki/class-doc
		$node.addClass( TEST_ELEMENT_CLASS );

		options.api = options.api || {};
		options.language = options.language || 'en';
		options.labelFormattingService = options.labelFormattingService || {
			getHtml: function ( id ) {
				return $.Deferred().resolve( id ).promise();
			}
		};

		return $node.grammaticalfeatureview( options || {} ).data( 'grammaticalfeatureview' );
	};

	QUnit.test( 'can be created', function ( assert ) {
		assert.ok( newGrammaticalFeatureView() instanceof $.wikibase.grammaticalfeatureview );
	} );

	QUnit.test( 'value can be injected as option.value', function ( assert ) {
		var view = newGrammaticalFeatureView( {
			value: [ 'Q1' ]
		} );

		assert.deepEqual( view.value(), [ 'Q1' ] );
	} );

	QUnit.test( 'value() sets internal value', function ( assert ) {
		var value1 = [ 'Q1' ],
			value2 = [ 'Q2' ],
			view = newGrammaticalFeatureView( {
				value: value1
			} );

		view.value( value2 );
		assert.equal( view.value(), value2 );
	} );

	QUnit.test( 'value() creates value from input if it is in edit mode', function ( assert ) {
		var view = newGrammaticalFeatureView();

		view.startEditing();
		view._grammaticalFeatureListWidget.allowedValues.push( 'Q1' );
		view._grammaticalFeatureListWidget.addTag( 'Q1', 'Q1-label' );

		assert.deepEqual( view.value(), [ 'Q1' ] );
	} );

	QUnit.test( 'should not be in edit mode when initialized without a value', function ( assert ) {
		assert.notOk( newGrammaticalFeatureView().isInEditMode() );
	} );

	QUnit.test( 'should not be in edit mode by default when initialized with a value', function ( assert ) {
		assert.notOk( newGrammaticalFeatureView( { value: [ 'Q1' ] } ).isInEditMode() );
	} );

	QUnit.test( 'draws value in input node after startEditing()', function ( assert ) {
		var view = newGrammaticalFeatureView( {
			value: [ 'Q1' ]
		} );

		view.startEditing();

		assert.deepEqual(
			view._grammaticalFeatureListWidget.getValue(),
			[ 'Q1' ]
		);
	} );

	QUnit.test( 'draws value in text node after stopEditing()', function ( assert ) {
		var done = assert.async();
		var labelFormattingService = {
			getHtml: function ( id ) {
				return $.Deferred().resolve( id + '-label' ).promise();
			}
		};
		var view = newGrammaticalFeatureView( {
			value: [ 'Q1' ],
			labelFormattingService: labelFormattingService
		} );

		view.startEditing();
		view.stopEditing();

		window.setTimeout( function () {
			assert.equal( view.$values.text(), 'Q1-label' );
			done();
		} );
	} );

	QUnit.test( 'draws multiple values in text node after stopEditing()', function ( assert ) {
		var done = assert.async();
		var labelFormattingService = {
			getHtml: function ( id ) {
				return $.Deferred().resolve( id + '-label' ).promise();
			}
		};
		var view = newGrammaticalFeatureView( {
			value: [ 'Q1', 'Q2' ],
			labelFormattingService: labelFormattingService
		} );

		view.startEditing();
		view.stopEditing();

		window.setTimeout( function () {
			assert.equal( view.$values.text(), 'Q1-label, Q2-label' );
			done();
		} );
	} );

	QUnit.test( 'should contain label connected to input through input id', function ( assert ) {
		var view = newGrammaticalFeatureView( {
			value: [ 'Q1' ]
		} );

		view.startEditing();

		var inputId = view.element.find( 'input' )[ 0 ].id;
		var labelsFound = view.element.find( 'label[for="' + inputId + '"]' );

		assert.equal(
			labelsFound.length,
			1
		);
	} );

}() );
