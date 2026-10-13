'use strict';

const { describeWithTestData } = require( './helpers/describeWithTestData' );
const { assertValidError } = require( './helpers/responseValidator' );
const {
	lexemeCreateRequests,
	lexemeEditRequests
} = require( './helpers/happyPathRequestBuilders' );

describeWithTestData( 'Edit metadata validation', (
	lexemeRequestInputs,
	describeEachRouteWithReset
) => {
	const editAndCreateRoutes = [
		...lexemeEditRequests( lexemeRequestInputs ),
		...lexemeCreateRequests( lexemeRequestInputs )
	];

	describeEachRouteWithReset( editAndCreateRoutes, ( newRequestBuilder ) => {
		it( 'responds 400 if an edit tag is invalid', async () => {
			assertValidError(
				await newRequestBuilder()
					.withJsonBodyParam( 'tags', [ 'not-a-real-tag' ] )
					.makeRequest(),
				400,
				'invalid-value',
				{ path: '/tags/0' }
			);
		} );

		it( 'responds 400 if the comment is too long', async () => {
			assertValidError(
				await newRequestBuilder()
					.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
					.makeRequest(),
				400,
				'value-too-long',
				{ path: '/comment', limit: 500 }
			);
		} );
	} );
} );
