/* eslint-env mocha */
'use strict';

const { assert } = require( 'api-testing' );
const { newCreateLexemeRequestBuilder } = require( './helpers/RequestBuilderFactory' );

describe( 'POST /entities/lexemes', () => {
	it( 'returns the created lexeme', async () => {
		const response = await newCreateLexemeRequestBuilder( {} )
			.makeRequest();

		assert.strictEqual( response.status, 201 );
		assert.strictEqual( response.body.id, 'L1' );
	} );
} );
