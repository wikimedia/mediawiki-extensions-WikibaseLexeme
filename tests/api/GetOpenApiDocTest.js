/* eslint-env mocha */
'use strict';

const { assert, REST } = require( 'api-testing' );

describe( 'GET /openapi.json', () => {
	const client = new REST( 'rest.php/wikibase/v1' );

	it( 'returns an OpenAPI document', async () => {
		const response = await client.get( '/openapi.json' );

		assert.strictEqual( response.status, 200, response.text );
		assert.isObject( response.body );
		assert.isString( response.body.openapi );
	} );
} );
