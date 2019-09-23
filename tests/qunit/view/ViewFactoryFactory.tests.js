( function ( wb, ViewFactoryFactory ) {
	'use strict';

	function getFactoryArgs() {
		return [
			sinon.stub(),
			{ getRevisionStore: sinon.stub(), getEntity: sinon.stub() },
			{ getAdder: sinon.stub() },
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			sinon.stub(),
			{ getMessage: sinon.stub() },
			sinon.stub(),
			[],
			'http://some-uri',
			'http://commons/api.php'
		];
	}

	QUnit.module( 'wikibase.lexeme.ViewFactoryFactory' );

	QUnit.test( 'returns ControllerViewFactory when editable', function ( assert ) {
		var factory = new ViewFactoryFactory(),
			result = factory.getViewFactory( true, getFactoryArgs() );

		// instanceof check across package module doesn't work
		// assert.ok( result instanceof ControllerViewFactory );
		assert.ok( result.getFormListView );
	} );

	QUnit.test( 'returns ReadModeViewFactory when not editable', function ( assert ) {
		var factory = new ViewFactoryFactory(),
			result = factory.getViewFactory( false, getFactoryArgs() );

		// instanceof check across package module doesn't work
		// assert.ok( result instanceof ReadModeViewFactory );
		assert.notOk( result.getFormListView );
	} );

}( wikibase, wikibase.lexeme.view.ViewFactoryFactory ) );
