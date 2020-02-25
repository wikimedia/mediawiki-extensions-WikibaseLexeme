/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	QUnit.module( 'wikibase.lexeme.widgets.ItemSelectorWidget' );

	var newInitializedItemSelectorWidget = function ( options ) {
		var $hiddenField = $( '<input>' )
				.attr( 'type', 'hidden' ),
			config = { $valueField: $hiddenField },
			widget = new wb.lexeme.widgets.ItemSelectorWidget( config );

		widget.initialize( options || { apiUrl: '-', language: '-', timeout: 100 } );

		return widget;
	};

	var executeWithWbsearchentitiesResponseStub = function ( response, assertionCallback ) {
		var ajaxStub = sinon.stub( $, 'ajax' );

		ajaxStub.returns( $.Deferred().resolve( response ).promise() );
		assertionCallback();
		ajaxStub.restore();
	};

	QUnit.test( 'test request parameters that must be sent to the API', function ( assert ) {
		var widget = newInitializedItemSelectorWidget(
				{ apiUrl: '-', language: 'someLanguage', timeout: 100 }
			),
			expectedParameters = {
				action: 'wbsearchentities',
				search: 'someSearchTerm',
				format: 'json',
				language: 'someLanguage',
				type: 'item',
				errorformat: 'plaintext',
				uselang: 'someLanguage' };

		assert.deepEqual( widget._getSearchApiParameters( 'someSearchTerm' ), expectedParameters );
	} );

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
			suggestions[ 0 ].getLabel(),
			searchEntitiesResults[ 0 ].label
		);
		assert.equal(
			suggestions[ 0 ].getData(),
			searchEntitiesResults[ 0 ].id
		);
		assert.equal(
			suggestions[ 1 ].getLabel(),
			searchEntitiesResults[ 1 ].label
		);
		assert.equal(
			suggestions[ 1 ].getData(),
			searchEntitiesResults[ 1 ].id
		);
	} );

	QUnit.test( 'item descriptions are included in the menu option labels', function ( assert ) {
		var widget = newInitializedItemSelectorWidget(),
			suggestions = widget.getLookupMenuOptionsFromData( [ {
				id: 'Q23',
				label: 'banana',
				description: 'fruit'
			} ] );

		assert.equal(
			suggestions[ 0 ].$element.find( '.oo-ui-labelElement-label' ).text(),
			'banana'
		);
		assert.equal(
			suggestions[ 0 ].$element.find( '.mw-widget-titleOptionWidget-description' ).text(),
			'fruit'
		);
	} );

	QUnit.test( 'labels and descriptions are escaped in menu options', function ( assert ) {
		var widget = newInitializedItemSelectorWidget(),
			suggestions = widget.getLookupMenuOptionsFromData( [ {
				id: 'Q1',
				label: '<b>duck</b>',
				description: '<i>aquatic bird</i>'
			} ] );

		assert.equal(
			suggestions[ 0 ].$element.find( '.oo-ui-labelElement-label' ).text(),
			'<b>duck</b>'
		);
		assert.equal(
			suggestions[ 0 ].$element.find( '.mw-widget-titleOptionWidget-description' ).text(),
			'<i>aquatic bird</i>'
		);
	} );

	QUnit.test( 'initialize throws error when required parameters are not provided', function ( assert ) {
		var $hiddenField = $( '<input>' )
				.attr( 'type', 'hidden' ),
			config = { $valueField: $hiddenField },
			widget = new wb.lexeme.widgets.ItemSelectorWidget( config );

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

	QUnit.test( 'constructor throws error when value field is not provided', function ( assert ) {
		assert.throws( function () {
			new wb.lexeme.widgets.ItemSelectorWidget( {} );
		} );
	} );

	QUnit.test( 'getLookupRequest if the ItemSelectorWidget was not initialized', function ( assert ) {
		var $hiddenField = $( '<input>' )
				.attr( 'type', 'hidden' ),
			config = { $valueField: $hiddenField },
			widget = new wb.lexeme.widgets.ItemSelectorWidget( config );

		executeWithWbsearchentitiesResponseStub(
			{ search: [] },
			function () {
				assert.throws( function () {
					widget.getLookupRequest();
				} );
			}
		);
	} );

}( wikibase ) );
