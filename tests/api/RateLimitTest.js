'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const {
	newAddLexemeStatementRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { getItemId, getStringPropertyId } = require( './helpers/entityHelper' );

describe( 'Rate Limiting', () => {
	let lexeme;
	let propertyId;

	before( async () => {
		lexeme = {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: await getItemId(),
			language: await getItemId()
		};
		propertyId = await getStringPropertyId();
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

	it( 'responds 429 when adding a statement and the edit rate limit is reached', async () => {
		const lexemeId = ( await newCreateLexemeRequestBuilder( lexeme )
			.makeRequest() ).body.id;

		const response = await newAddLexemeStatementRequestBuilder(
			lexemeId,
			{
				property: { id: propertyId },
				value: { type: 'value', content: 'potato' }
			}
		)
			.withConfigOverride( 'wgRateLimits', { edit: { anon: [ 0, 60 ] } } )
			.makeRequest();

		expect( response ).to.have.status( 429 );
		assert.strictEqual( response.body.code, 'request-limit-reached' );
		assert.strictEqual(
			response.body.message,
			'Exceeded the limit of actions that can be performed in a given span of time'
		);
		assert.deepStrictEqual(
			response.body.context,
			{ reason: 'rate-limit-reached' }
		);
	} );

} );
