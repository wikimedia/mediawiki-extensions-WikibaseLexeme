'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const { getLatestEditMetadata, newStatementWithRandomStringValue } = require( './helpers/entityHelper' );
const { makeEtag } = require( './helpers/httpHelper' );
const rbf = require( './helpers/RequestBuilderFactory' );
const { describeWithTestData } = require( './helpers/describeWithTestData' );

const getLexemeRequests = ( requestInputs ) => ( [
	() => rbf.newGetLexemeRequestBuilder( requestInputs.lexemeId )
].map( ( newRequestBuilder ) => ( { newRequestBuilder, requestInputs } ) ) );

const lexemeEditRequests = ( requestInputs ) => ( [
	() => rbf.newAddLexemeStatementRequestBuilder(
		requestInputs.lexemeId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	)
].map( ( newRequestBuilder ) => ( { newRequestBuilder, requestInputs } ) ) );

function assertValid200Response( response, revisionId, lastModified ) {
	expect( response ).to.have.status( 200 );
	assert.equal( response.header[ 'last-modified' ], lastModified );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
}

async function assertValid201Response( response, lexemeId ) {
	expect( response ).to.have.status( 201 );
	const latestRevision = await getLatestEditMetadata( lexemeId );
	assert.equal( response.header[ 'last-modified' ], latestRevision.timestamp );
	assert.equal( response.header.etag, makeEtag( latestRevision.revid ) );
}

function assertValid304Response( response, revisionId ) {
	expect( response ).to.have.status( 304 );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
	assert.equal( response.text, '' );
}

function assertValid412Response( response ) {
	expect( response ).to.have.status( 412 );
	assert.isUndefined( response.header.etag );
	assert.isUndefined( response.header[ 'last-modified' ] );
	assert.isEmpty( response.text );
}

describeWithTestData( 'Conditional requests', (
	lexemeRequestInputs,
	describeEachRouteWithReset
) => {

	const getRoutes = [
		...getLexemeRequests( lexemeRequestInputs )
	];
	const editRoutes = [
		...lexemeEditRequests( lexemeRequestInputs )
	];

	describeEachRouteWithReset( getRoutes, ( newRequestBuilder, requestInputs ) => {
		// eslint-disable-next-line mocha/no-top-level-hooks
		before( async () => {
			const latestRevision = await getLatestEditMetadata( requestInputs.lexemeId );
			requestInputs.latestRevId = latestRevision.revid;
			requestInputs.latestRevTimestamp = latestRevision.timestamp;
		} );

		describe( 'If-None-Match - 200 response', () => {
			it( 'if the current revision is newer than the ETag provided', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId - 1 ) )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'if the current revision is newer than any of the ETags provided', async () => {
				const ifNoneMatchHeader = makeEtag( requestInputs.latestRevId - 1, requestInputs.latestRevId - 2 );
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', ifNoneMatchHeader )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'if the provided ETag is not a valid revision ID', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', '"foo"' )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'if all the provided ETags are not valid revision IDs', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', makeEtag( 'foo', 'bar' ) )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'if the current revision is newer than an ETag (while other ETags are invalid)', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', makeEtag( 'foo', requestInputs.latestRevId - 1, 'bar' ) )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'if the header is invalid', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', 'not in spec for an If-None-Match header - 200 response' )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Modified-Since', requestInputs.latestRevTimestamp )
					// the If-None-Match header on its own would return 304
					.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId - 1 ) )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );
		} );

		describe( 'If-None-Match - 304 response', () => {
			it( 'if the current revision ID matches the ETag provided', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId ) )
					.makeRequest();

				assertValid304Response( response, requestInputs.latestRevId );
			} );

			it( 'if the current revision ID matches one of the ETags provided', async () => {
				const ifNoneMatchHeader = makeEtag( requestInputs.latestRevId - 1, requestInputs.latestRevId );
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', ifNoneMatchHeader )
					.makeRequest();

				assertValid304Response( response, requestInputs.latestRevId );
			} );

			it( 'if the current revision matches one of the ETags (while other ETags are invalid)', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', makeEtag( 'foo', requestInputs.latestRevId ) )
					.makeRequest();

				assertValid304Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'if the header is *', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', '*' )
					.makeRequest();

				assertValid304Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
				const response = await newRequestBuilder()
					// the If-Modified-Since header on its own would return 200
					.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
					.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId ) )
					.makeRequest();

				assertValid304Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );
		} );

		describe( 'If-Match - 200 response', () => {
			it( 'if the current revision matches the ETag provided', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId ) )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'if the header is *', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', '*' )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'if the current revision matches one of the ETags provided', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId - 1, requestInputs.latestRevId ) )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );
		} );

		describe( 'If-Match - 412 response', () => {
			it( 'if the provided ETag is a previous revision ID', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId - 1 ) )
					.makeRequest();

				assertValid412Response( response );
			} );

			it( 'if all the provided ETags are previous revision IDs', async () => {
				const ifMatchHeader = makeEtag( requestInputs.latestRevId - 1, requestInputs.latestRevId - 2 );
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', ifMatchHeader )
					.makeRequest();

				assertValid412Response( response );
			} );

			it( 'if the provided ETag is not a valid revision ID', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', '"foo"' )
					.makeRequest();

				assertValid412Response( response );
			} );
		} );

		describe( 'If-Modified-Since - 200 response', () => {
			it( 'If-Modified-Since header is older than current revision', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );
		} );

		describe( 'If-Modified-Since - 304 response', () => {
			it( 'If-Modified-Since header is same as current revision', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Modified-Since', requestInputs.latestRevTimestamp )
					.makeRequest();

				assertValid304Response( response, requestInputs.latestRevId );
			} );

			it( 'If-Modified-Since header is after current revision', async () => {
				const futureDate = new Date(
					new Date( requestInputs.latestRevTimestamp ).getTime() + 5000
				).toUTCString();
				const response = await newRequestBuilder()
					.withHeader( 'If-Modified-Since', futureDate )
					.makeRequest();

				assertValid304Response( response, requestInputs.latestRevId );
			} );
		} );

		describe( 'If-Unmodified-Since - 200 response', () => {
			it( 'If-Unmodified-Since header is same as current revision', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Unmodified-Since', requestInputs.latestRevTimestamp )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );

			it( 'If-Unmodified-Since header is after current revision', async () => {
				const futureDate = new Date(
					new Date( requestInputs.latestRevTimestamp ).getTime() + 5000
				).toUTCString();
				const response = await newRequestBuilder()
					.withHeader( 'If-Unmodified-Since', futureDate )
					.makeRequest();

				assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
			} );
		} );

		it( 'responds 412 given If-Unmodified-Since is before current revision', async () => {
			const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
			const response = await newRequestBuilder()
				.withHeader( 'If-Unmodified-Since', yesterday )
				.makeRequest();

			assertValid412Response( response );
		} );
	} );

	describeEachRouteWithReset( editRoutes, ( newRequestBuilder, requestInputs ) => {
		// eslint-disable-next-line mocha/no-top-level-hooks
		beforeEach( async () => {
			const latestRevision = await getLatestEditMetadata( requestInputs.lexemeId );
			requestInputs.latestRevId = latestRevision.revid;
			requestInputs.latestRevTimestamp = latestRevision.timestamp;
		} );

		describe( 'If-Match', () => {
			it( 'responds 201 given If-Match is the current revision ETag', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId ) )
					.makeRequest();

				await assertValid201Response( response, requestInputs.lexemeId );
			} );

			it( 'responds 412 given If-Match is a previous revision ETag', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId - 1 ) )
					.makeRequest();

				assertValid412Response( response );
			} );

			it( 'responds 201 given If-Match is *', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', '*' )
					.makeRequest();

				await assertValid201Response( response, requestInputs.lexemeId );
			} );
		} );

		describe( 'If-None-Match', () => {
			it( 'responds 412 given If-None-Match is the current revision ETag', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId ) )
					.makeRequest();

				assertValid412Response( response );
			} );

			it( 'responds 201 given If-None-Match is a previous revision ETag', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId - 1 ) )
					.makeRequest();

				await assertValid201Response( response, requestInputs.lexemeId );
			} );

			it( 'responds 412 given If-None-Match is *', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-None-Match', '*' )
					.makeRequest();

				assertValid412Response( response );
			} );
		} );

		describe( 'If-Unmodified-Since', () => {
			it( 'responds 201 given If-Unmodified-Since is the current revision timestamp', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Unmodified-Since', requestInputs.latestRevTimestamp )
					.makeRequest();

				await assertValid201Response( response, requestInputs.lexemeId );
			} );

			it( 'responds 412 given If-Unmodified-Since is before the current revision timestamp', async () => {
				const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
				const response = await newRequestBuilder()
					.withHeader( 'If-Unmodified-Since', yesterday )
					.makeRequest();

				assertValid412Response( response );
			} );
		} );

		// If-Modified-Since is ignored for edit requests; it only applies to GET and HEAD.
		describe( 'If-Modified-Since', () => {
			it( 'responds 201 because If-Modified-Since is ignored for edit requests', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Modified-Since', requestInputs.latestRevTimestamp )
					.makeRequest();

				await assertValid201Response( response, requestInputs.lexemeId );
			} );
		} );
	} );

} );
