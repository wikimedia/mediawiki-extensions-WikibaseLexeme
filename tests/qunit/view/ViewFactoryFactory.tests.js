( function ( wb, ViewFactoryFactory ) {
	'use strict';

	var sandbox = sinon.sandbox.create();

	QUnit.module( 'wikibase.lexeme.view.ViewFactoryFactory', {
		beforeEach: function () {
			sandbox.stub( wikibase.lexeme, 'ControllerViewFactory' );
			sandbox.stub( wikibase.lexeme.view, 'ReadModeViewFactory' );
		},
		afterEach: function () {
			sandbox.restore();
		}
	} );

	QUnit.test( 'returns ControllerViewFactory when editable', function ( assert ) {
		var factory = new ViewFactoryFactory(),
			result = factory.getViewFactory( true, [] );

		sinon.assert.calledWithNew( wikibase.lexeme.ControllerViewFactory );
		assert.ok( result instanceof wikibase.lexeme.ControllerViewFactory );
	} );

	QUnit.test( 'returns ReadModeViewFactory when not editable', function ( assert ) {
		var factory = new ViewFactoryFactory(),
			result = factory.getViewFactory( false, [] );

		sinon.assert.calledWithNew( wikibase.lexeme.view.ReadModeViewFactory );
		assert.ok( result instanceof wikibase.lexeme.view.ReadModeViewFactory );
	} );

	QUnit.test( 'ControllerViewFactory is called with correct arguments', function ( assert ) {
		var factory = new ViewFactoryFactory();

		factory.getViewFactory( true, [ 1, 2, 3 ] );

		assert.ok( wikibase.lexeme.ControllerViewFactory.calledWith( 1, 2, 3 ) );
	} );

	QUnit.test( 'ReadModeViewFactory is called with correct arguments', function ( assert ) {
		var factory = new ViewFactoryFactory();

		factory.getViewFactory( false, [ 1, 2, 3 ] );

		assert.ok( wikibase.lexeme.view.ReadModeViewFactory.calledWith( 3 ) );
	} );

}( wikibase, wikibase.lexeme.view.ViewFactoryFactory ) );
