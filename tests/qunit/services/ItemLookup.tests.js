/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit ) {
	QUnit.module( 'wikibase.lexeme.services.ItemLookup' );

	var getMockApiWithResponse = function ( response ) {
			return {
				getEntities: function () {
					var deferred = $.Deferred();
					deferred.resolve( response );
					return deferred;
				}
			};
		},
		getFailingMockApi = function () {
			return {
				getEntities: function () {
					var deferred = $.Deferred();
					deferred.reject();
					return deferred;
				}
			};
		},
		newLookupWithApi = function ( api ) {
			return new wb.lexeme.services.ItemLookup( api );
		};

	QUnit.test( 'requires RepoApi', function ( assert ) {
		assert.throws( function () {
			new wb.lexeme.services.ItemLookup();
		} );
	} );

	QUnit.test( 'returns the entity from the API response', function ( assert ) {
		assert.expect( 1 );

		var responseItem = { id: 'Q123' },
			lookup = newLookupWithApi( getMockApiWithResponse( {
				entities: {
					Q123: responseItem
				}
			} ) );

		lookup.fetchEntity( 'Q123' ).done( function ( item ) {
			assert.deepEqual( responseItem, item );
		} );
	} );

	QUnit.test( 'fails for failing API', function ( assert ) {
		assert.expect( 1 );

		var lookup = newLookupWithApi( getFailingMockApi() );

		lookup.fetchEntity( 'Q123' ).fail( function () {
			assert.ok( true );
		} );
	} );

	QUnit.test( 'fails for unexpected API response', function ( assert ) {
		assert.expect( 1 );

		var lookup = newLookupWithApi( getMockApiWithResponse( { foo: 'bar' } ) );

		lookup.fetchEntity( 'Q123' ).fail( function () {
			assert.ok( true );
		} );
	} );

}( wikibase, jQuery, QUnit ) );
