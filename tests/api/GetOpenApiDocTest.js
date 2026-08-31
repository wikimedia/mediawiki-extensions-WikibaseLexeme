'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const { RequestBuilder } = require( './helpers/RequestBuilder' );

const committedFragment = require( '../../src/MediaWiki/RestApi/specs/openapi.fragment.dereferenced.json' );

const LEXEME_PATH = '/v0/entities/lexemes/{lexeme_id}';

describe( 'GET /v1/openapi.json', () => {

	it( 'documents the lexeme route', async () => {
		const response = await new RequestBuilder()
			.withRoute( 'GET', '/v1/openapi.json' )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.deepStrictEqual( response.body.paths[ LEXEME_PATH ], committedFragment.paths[ LEXEME_PATH ] );
		assert.ok( response.body.tags.some( ( tag ) => tag.name === 'lexemes' ) );
		// the Wikibase-owned document is still there underneath
		assert.ok( '/v1/openapi.json' in response.body.paths );
	} );

} );
