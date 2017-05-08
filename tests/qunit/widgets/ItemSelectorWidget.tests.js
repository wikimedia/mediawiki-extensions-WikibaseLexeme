/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit, sinon ) {
	QUnit.module( 'wikibase.lexeme.widgets.ItemSelectorWidget' );

	var newInitializedItemSelectorWidget = function () {
		var widget = new wb.lexeme.widgets.ItemSelectorWidget();

		widget.initialize( {
			apiUrl: '-',
			language: '-',
			timeout: 100
		} );

		return widget;
	};

	var executeWithWbsearchentitiesResponseStub = function ( response, assertionCallback ) {
		var ajaxStub = sinon.stub( jQuery, 'ajax' );

		ajaxStub.returns( $.Deferred().resolve( response ).promise() );
		assertionCallback();
		ajaxStub.restore();
	};

	QUnit.test( 'getLookupRequest returns request results', function ( assert ) {
		var widget = newInitializedItemSelectorWidget(),
			searchEntitiesResults = [ { id: 'Q123', label: 'English' }, { id: 'Q234', label: 'German' } ];

		executeWithWbsearchentitiesResponseStub(
			{ search: searchEntitiesResults },
			function () {
				widget.getLookupRequest().done( function ( items ) {
					assert.deepEqual( items, searchEntitiesResults );
				} );
			}
		);
	} );

	QUnit.test( 'getLookupMenuOptionsFromData returns suggestions from results', function ( assert ) {
		var widget = newInitializedItemSelectorWidget(),
			searchEntitiesResults = [ { id: 'Q123', label: 'English' }, { id: 'Q234', label: 'German' } ],
			suggestions = widget.getLookupMenuOptionsFromData( searchEntitiesResults );

		assert.equal( suggestions.length, searchEntitiesResults.length );
		assert.equal(
			suggestions[ 0 ].label,
			searchEntitiesResults[ 0 ].label + ' (' + searchEntitiesResults[ 0 ].id + ')'
		);
		assert.equal( suggestions[ 0 ].id, searchEntitiesResults[ 0 ].data );
		assert.equal(
			suggestions[ 1 ].label,
			searchEntitiesResults[ 1 ].label + ' (' + searchEntitiesResults[ 1 ].id + ')'
		);
		assert.equal( suggestions[ 1 ].id, searchEntitiesResults[ 1 ].data );
	} );

	QUnit.test( 'initialize throws error when required parameters are not provided', function ( assert ) {
		var widget = new wb.lexeme.widgets.ItemSelectorWidget();

		assert.throws( function () {
			widget.initialize( { apiUrl: null, language: 'en', timeout: 100 } );
		} );
		assert.throws( function () {
			widget.initialize( { apiUrl: 'some-url', language: null, timeout: 100 } );
		} );
		assert.throws( function () {
			widget.initialize( { apiUrl: 'some-url', language: 'en', timeout: null } );
		} );
	} );

	QUnit.test( 'getLookupRequest if the ItemSelectorWidget was not initialized', function ( assert ) {
		var widget = new wb.lexeme.widgets.ItemSelectorWidget();

		executeWithWbsearchentitiesResponseStub(
			{ search: [] },
			function () {
				assert.throws( function () {
					widget.getLookupRequest();
				} );
			}
		);
	} );

}( wikibase, jQuery, QUnit, sinon ) );
