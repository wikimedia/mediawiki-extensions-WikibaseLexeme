/* eslint-env mocha */
'use strict';

const { assert, REST } = require( 'api-testing' );

describe( 'GET /openapi.json', function () {
	const client = new REST( 'rest.php/wikibase/v1' );

	it( 'returns an OpenAPI document', function () {
		return client.get( '/openapi.json' ).then( function ( response ) {
			assert.strictEqual( response.status, 200, response.text );
			assert.isObject( response.body );
			assert.isString( response.body.openapi );
		} );
	} );
} );
