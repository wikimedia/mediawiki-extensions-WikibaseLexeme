'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const {
	newCreateLexemeRequestBuilder,
	newCreateItemRequestBuilder
} = require( './helpers/RequestBuilderFactory' );

describe( 'Rate Limiting', () => {
	let lexeme;

	before( async () => {
		lexeme = {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id,
			language: ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id
		};
	} );

	it( 'responds 429 when the edit rate limit is reached', async () => {
		const response = await newCreateLexemeRequestBuilder( lexeme )
			.withConfigOverride( 'wgRateLimits', { edit: { anon: [ 0, 60 ] } } )
			.makeRequest();

		expect( response ).to.have.status( 429 );
		assert.strictEqual( response.body.code, 'request-limit-reached' );
		assert.strictEqual(
			response.body.message,
			'Exceeded the limit of actions that can be performed in a given span of time'
		);
		assert.deepStrictEqual( response.body.context, { reason: 'rate-limit-reached' } );
	} );

} );
