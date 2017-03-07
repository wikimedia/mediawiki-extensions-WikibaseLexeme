/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit ) {
	QUnit.module( 'wikibase.lexeme.widgets.LanguageLookupWidget' );

	var newInitializedLanguageLookupWidget = function () {
		var widget = new wb.lexeme.widgets.LanguageLookupWidget();

		widget.initialize( {
			apiUrl: '-',
			language: '-',
			timeout: 100
		} );

		return widget;
	};

	var stubWbsearchentitiesResponse = function ( response ) {
		$.ajax = function () {
			var deferred = $.Deferred().resolve( response );
			return deferred.promise();
		}
	};

	QUnit.test( 'getLookupRequest returns request results', function ( assert ) {
		var widget = newInitializedLanguageLookupWidget(),
			searchEntitiesResults = [ { id: 'Q123', label: 'English' }, { id: 'Q234', label: 'German' } ];

		stubWbsearchentitiesResponse( { search: searchEntitiesResults } );

		widget.getLookupRequest().done( function ( items ) {
			assert.deepEqual( items, searchEntitiesResults );
		} );
	} );

	QUnit.test( 'getLookupMenuOptionsFromData returns suggestions from results', function ( assert ) {
		var widget = newInitializedLanguageLookupWidget(),
			searchEntitiesResults = [ { id: 'Q123', label: 'English' }, { id: 'Q234', label: 'German' } ],
			suggestions = widget.getLookupMenuOptionsFromData( searchEntitiesResults );

		assert.equal( searchEntitiesResults.length, suggestions.length );
		assert.equal( searchEntitiesResults[ 0 ].label, suggestions[ 0 ].label );
		assert.equal( searchEntitiesResults[ 0 ].data, suggestions[ 0 ].id );
		assert.equal( searchEntitiesResults[ 1 ].label, suggestions[ 1 ].label );
		assert.equal( searchEntitiesResults[ 1 ].data, suggestions[ 1 ].id );
	} );

	QUnit.test( 'initialize throws error when required parameters are not provided', function ( assert ) {
		var widget = new wb.lexeme.widgets.LanguageLookupWidget();

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

	QUnit.test( 'getLookupRequest if the LanguageLookupWidget was not initialized', function ( assert ) {
		var widget = new wb.lexeme.widgets.LanguageLookupWidget();

		stubWbsearchentitiesResponse( { search: [] } );

		assert.throws( function () {
			widget.getLookupRequest();
		} );
	} )

}( wikibase, jQuery, QUnit ) );
