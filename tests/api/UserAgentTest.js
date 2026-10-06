'use strict';

const { describeWithTestData } = require( './helpers/describeWithTestData' );
const { assert } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const {
	lexemeCreateRequests,
	lexemeEditRequests,
	lexemeGetRequests
} = require( './helpers/happyPathRequestBuilders' );

function assertValid400Response( response ) {
	expect( response ).to.have.status( 400 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, 'missing-user-agent' );
	assert.include( response.body.message, 'User-Agent' );
}

describeWithTestData( 'User-Agent requests', (
	lexemeRequestInputs,
	describeEachRouteWithReset
) => {

	const routes = [
		...lexemeEditRequests( lexemeRequestInputs ),
		...lexemeCreateRequests( lexemeRequestInputs ),
		...lexemeGetRequests( lexemeRequestInputs )
	];

	describeEachRouteWithReset( routes, ( newRequestBuilder ) => {
		it( 'No User-Agent header provided', async () => {
			const requestBuilder = newRequestBuilder();
			delete requestBuilder.headers[ 'user-agent' ];
			const response = await requestBuilder
				.makeRequest();

			assertValid400Response( response );
		} );

		it( 'Empty User-Agent header provided', async () => {
			const response = await newRequestBuilder()
				.withHeader( 'user-agent', '' )
				.makeRequest();

			assertValid400Response( response );
		} );
	} );
} );
