'use strict';

const { describeWithTestData } = require( './helpers/describeWithTestData' );
const { assert } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const entityHelper = require( './helpers/entityHelper' );
const { newStatementWithRandomStringValue } = entityHelper;
const {
	newAddLexemeStatementRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );

const lexemeEditRequests = ( requestInputs ) => ( [
	() => newAddLexemeStatementRequestBuilder(
		requestInputs.lexemeId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	)
].map( ( newRequestBuilder ) => ( { newRequestBuilder, requestInputs } ) ) );

const lexemeCreateRequest = ( requestInputs ) => ( {
	newRequestBuilder: () => newCreateLexemeRequestBuilder( {
		lemmas: requestInputs.lemmas,
		language: requestInputs.language,
		lexical_category: requestInputs.lexicalCategory
	} ),
	requestInputs
} );

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

	const editRoutes = [
		...lexemeEditRequests( lexemeRequestInputs )
	];
	const editAndCreateRoutes = [
		...editRoutes,
		lexemeCreateRequest( lexemeRequestInputs )
	];

	describeEachRouteWithReset( editAndCreateRoutes, ( newRequestBuilder ) => {
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
