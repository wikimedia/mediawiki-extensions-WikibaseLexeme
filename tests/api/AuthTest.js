'use strict';

const { requireExtensions } = require( '../../../Wikibase/tests/api-testing/utils' );
const { describeWithTestData } = require( './helpers/describeWithTestData' );
const { assert, action } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const {
	changeLexemeProtectionStatus,
	newStatementWithRandomStringValue
} = require( './helpers/entityHelper' );
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

const { getOrCreateAuthTestUser } = require( './helpers/testUsers' );
const { assertValidError } = require( './helpers/responseValidator' );
const { runAllJobs } = require( 'api-testing/lib/wiki' );

describeWithTestData( 'Auth', (
	lexemeRequestInputs,
	describeEachRouteWithReset
) => {
	let user;
	let root;

	// eslint-disable-next-line mocha/no-top-level-hooks
	before( async () => {
		// using a single-purpose user here because blocking it might interfere with other tests
		user = await getOrCreateAuthTestUser();
		root = await action.root();
	} );

	const editRoutes = [
		...lexemeEditRequests( lexemeRequestInputs )
	];
	const editAndCreateRoutes = [
		...editRoutes,
		lexemeCreateRequest( lexemeRequestInputs )
	];

	describe( 'Authentication', () => {
		describeEachRouteWithReset( editAndCreateRoutes, ( newRequestBuilder ) => {
			it( 'has an X-Authenticated-User header with the logged in user', async () => {
				const response = await newRequestBuilder().withUser( user ).makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				assert.header( response, 'X-Authenticated-User', user.username );
			} );

			describe.skip( 'OAuth', () => {
				before( requireExtensions( [ 'OAuth' ] ) );

				it( 'responds with an error given an invalid bearer token', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'Authorization', 'Bearer this-is-an-invalid-token' )
						.makeRequest();

					expect( response ).to.have.status( 403 );
				} );
			} );
		} );
	} );

	describe( 'Authorization', () => {
		describe( 'Blocked user', () => {
			before( async () => {
				await root.action( 'block', {
					user: user.username,
					reason: 'testing',
					token: await root.token()
				}, 'POST' );
			} );

			after( async () => {
				await root.action( 'unblock', {
					user: user.username,
					token: await root.token()
				}, 'POST' );
			} );

			describeEachRouteWithReset( editAndCreateRoutes, ( newRequestBuilder ) => {
				it( 'cannot create/edit if blocked', async () => {
					assertValidError(
						await newRequestBuilder().withUser( user ).makeRequest(),
						403,
						'permission-denied',
						{ denial_reason: 'blocked-user' }
					);
				} );
			} );
		} );

		describe( 'Globally blocked user', () => {
			let ranGlobalBlock = false;

			before( async function () {
				await requireExtensions( [ 'GlobalBlocking' ] ).call( this );
				await root.addGroups( root.username, [ 'steward' ] );
				await root.action( 'globalblock', {
					target: user.username,
					reason: 'testing',
					expiry: '1 hour',
					token: await root.token()
				}, 'POST' );

				ranGlobalBlock = true;
			} );

			after( async () => {
				if ( !ranGlobalBlock ) {
					return;
				}

				await root.action( 'globalblock', {
					target: user.username,
					reason: 'testing',
					unblock: true,
					token: await root.token()
				}, 'POST' );
			} );

			describeEachRouteWithReset( editAndCreateRoutes, ( newRequestBuilder ) => {
				it( 'cannot create/edit if blocked globally', async () => {
					assertValidError(
						await newRequestBuilder().withUser( user ).makeRequest(),
						403,
						'permission-denied',
						{ denial_reason: 'blocked-user' }
					);
				} );
			} );
		} );

		// protecting/unprotecting does not always take effect immediately. These tests are isolated here to avoid
		// accidentally testing against a protected page in the other tests and receiving false positive results.
		editRoutes.forEach( ( { newRequestBuilder, requestInputs } ) => {
			describe( `Protected entity page - ${ newRequestBuilder().getRouteDescription() }`, () => {
				before( async () => {
					await changeLexemeProtectionStatus( requestInputs.mainTestSubject, 'sysop' ); // protect
				} );

				after( async () => {
					await changeLexemeProtectionStatus( requestInputs.mainTestSubject, 'all' ); // unprotect
					await runAllJobs();
				} );

				it( `Permission denied - ${ newRequestBuilder().getRouteDescription() }`, async function () {
					// this test often hits a race condition where this request is made before the entity is protected
					this.retries( 3 );

					assertValidError(
						await newRequestBuilder().makeRequest(),
						403,
						'permission-denied',
						{ denial_reason: 'resource-protected' }
					);
				} );
			} );
		} );

		it( 'cannot create a lexeme without the createpage permission', async () => {
			const response = await newCreateLexemeRequestBuilder( {
				lemmas: lexemeRequestInputs.lemmas,
				language: lexemeRequestInputs.language,
				lexical_category: lexemeRequestInputs.lexicalCategory
			} )
				.withUser( await action.alice() )
				.withConfigOverride( 'wgGroupPermissions', {
					'*': { read: true, edit: true, createpage: false },
					user: { read: true, edit: true, createpage: false }
				} ).makeRequest();

			expect( response ).to.have.status( 403 );
			assert.strictEqual( response.body.error, 'rest-write-denied' );
		} );
	} );
} );
