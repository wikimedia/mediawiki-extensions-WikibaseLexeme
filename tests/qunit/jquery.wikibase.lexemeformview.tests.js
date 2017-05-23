/**
 * @license GPL-2.0+
 */
( function ( $, wb, QUnit ) {
	'use strict';

	var TEST_LEXMEFORMVIEW_CLASS = 'test_lexemeformview';

	QUnit.module( 'jquery.wikibase.lexemeformview', QUnit.newMwEnvironment( {
		teardown: function () {
			$( '.' + TEST_LEXMEFORMVIEW_CLASS ).remove();
		}
	} ) );

	var newLexemeFormView = function ( options ) {
		var $node = $( '<div/>' ).appendTo( 'body' );
		options = options || {};

		$node.addClass( TEST_LEXMEFORMVIEW_CLASS );

		options.api = options.api || {};
		options.language = options.language || 'en';
		options.labelFormattingService = options.labelFormattingService || {
			getHtml: function ( id ) {
				return $.Deferred().resolve( id ).promise();
			}
		};
		options.buildStatementGroupListView = function () {};

		return $node.lexemeformview( options || {} ).data( 'lexemeformview' );
	};

	var newForm = function ( id, representation ) {
		return new wb.lexeme.datamodel.LexemeForm( id, representation );
	};

	QUnit.test( 'can be created', function ( assert ) {
		assert.ok( newLexemeFormView() instanceof $.wikibase.lexemeformview );
	} );

	QUnit.test( 'value can be injected as option.value', function ( assert ) {
		var form = newForm( 'F123', 'foo' ),
			view = newLexemeFormView( {
				value: form
			} );

		assert.equal( view.value(), form );
	} );

	QUnit.test( 'value() sets internal value', function ( assert ) {
		var form1 = newForm( 'F123', 'foo' ),
			form2 = newForm( 'F234', 'bar' ),
			view = newLexemeFormView( {
				value: form1
			} );

		view.value( form2 );
		assert.equal( view.value(), form2 );
	} );

	QUnit.test( 'value() creates value from input if it is in edit mode', function ( assert ) {
		var view = newLexemeFormView(),
			textInput = 'foobar';

		view.startEditing();
		view.element.find( view.options.inputNodeName ).val( textInput );

		assert.equal( view.value().getRepresentation(), textInput );
	} );

	QUnit.test( 'should be in edit mode by default when initialized without a value', function ( assert ) {
		assert.ok( newLexemeFormView().isInEditMode() );
	} );

	QUnit.test( 'should not be in edit mode by default when initialized with a value', function ( assert ) {
		assert.notOk( newLexemeFormView( { value: newForm( 'F123', 'foo' ) } ).isInEditMode() );
	} );

	QUnit.test( 'draws value in input node after startEditing()', function ( assert ) {
		var form = newForm( 'F123', 'foobar' ),
			view = newLexemeFormView( {
				value: form
			} );

		view.startEditing();
		assert.equal(
			view.element.find( view.options.inputNodeName ).val(),
			form.getRepresentation()
		);
	} );

	QUnit.test( 'draws value in text node after stopEditing()', function ( assert ) {
		var form = newForm( 'F123', 'foobar' ),
			view = newLexemeFormView( {
				value: form
			} );

		view.startEditing();
		view.stopEditing();
		assert.equal(
			view.element.find( '.wikibase-lexeme-form-text' ).text(),
			form.getRepresentation()
		);
	} );

}( jQuery, wikibase, QUnit ) );
