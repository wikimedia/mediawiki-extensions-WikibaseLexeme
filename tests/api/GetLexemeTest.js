/* eslint-env mocha */
'use strict';

const { assert, REST } = require( 'api-testing' );

const client = new REST( 'rest.php/wikibase/v0' );
// TODO create something like Wikibase.ci.php to avoid loading routes this way
client.req.set(
	'X-Config-Override',
	JSON.stringify( {
		wgRestAPIAdditionalRouteFiles: [
			'extensions/WikibaseLexeme/src/MediaWiki/RestApi/routes.dev.json'
		]
	} )
);
describe( 'GET /entities/lexemes/{lexeme_id}', () => {
	it( 'returns the lexeme with the requested ID', async () => {
		const response = await client.get( '/entities/lexemes/L123' );

		assert.strictEqual( response.status, 200, response.text );
		assert.deepStrictEqual( response.body, { id: 'L123' } );
	} );
} );
