'use strict';

const { describeWithTestData } = require( './helpers/describeWithTestData' );
const { assertValidError } = require( './helpers/responseValidator' );
const {
	lexemeCreateRequests,
	lexemeEditRequests
} = require( './helpers/happyPathRequestBuilders' );

describeWithTestData( 'Rate Limiting', ( lexemeRequestInputs ) => {

	[
		...lexemeEditRequests( lexemeRequestInputs ),
		...lexemeCreateRequests( lexemeRequestInputs )
	].forEach( ( { newRequestBuilder } ) => {
		it( `${ newRequestBuilder().getRouteDescription() } responds 429 when the edit rate limit is reached`, async () => {
			const response = await newRequestBuilder()
				.withConfigOverride( 'wgRateLimits', { edit: { anon: [ 0, 60 ] } } )
				.makeRequest();

			assertValidError(
				response,
				429,
				'request-limit-reached',
				{ reason: 'rate-limit-reached' }
			);
		} );
	} );
} );
