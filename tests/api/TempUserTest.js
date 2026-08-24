'use strict';

const { action, assert, utils } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const entityHelper = require( './helpers/entityHelper' );
const {
	newCreateLexemeRequestBuilder,
	newCreateItemRequestBuilder
} = require( './helpers/RequestBuilderFactory' );

describe( 'IP masking', () => {
	let languageId;
	let lexicalCategoryId;
	let newRequestBuilder;
	const tempUserPrefix = 'TempUserTest';

	before( async () => {
		languageId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		lexicalCategoryId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		const lemma = `test-lemma-${ utils.uniq() }`;

		const lexeme = {
			lemmas: { en: lemma },
			lexical_category: lexicalCategoryId,
			language: languageId
		};

		newRequestBuilder = () => newCreateLexemeRequestBuilder( lexeme );
	} );

	function withTempUsersEnabled( requestBuilder ) {
		return requestBuilder.withConfigOverride( 'wgAutoCreateTempUser', {
			enabled: true,
			genPattern: `${ tempUserPrefix } $1`
		} );
	}

	it( 'makes an edit as an IP user with tempUser disabled', async () => {
		const response = await newRequestBuilder()
			.withConfigOverride( 'wgAutoCreateTempUser', { enabled: false } )
			.makeRequest();

		expect( response ).status.to.be.within( 200, 299 );
		const { user } = await entityHelper.getLatestEditMetadata( response.body.id );

		assert.match( user, /^\d+\.\d+\.\d+\.\d+$/ );
	} );

	describe( 'temp user creation', () => {
		it( 'makes an edit as a temp user with tempUser enabled', async () => {
			const response = await withTempUsersEnabled( newRequestBuilder() ).makeRequest();

			expect( response ).status.to.be.within( 200, 299 );
			const { user } = await entityHelper.getLatestEditMetadata( response.body.id );
			assert.include( user, tempUserPrefix );
			assert.header( response, 'X-Temporary-User-Created', user );
		} );

		it( 'responds 429 when the temp user creation limit is reached', async () => {
			const requestBuilder = withTempUsersEnabled( newRequestBuilder() )
				// -1 means CACHE_ANYTHING. This is needed because the throttler relies on the cache.
				.withConfigOverride( 'wgMainCacheType', -1 )
				.withConfigOverride( 'wgTempAccountCreationThrottle', [ { count: 1, seconds: 86400 } ] );

			await requestBuilder.makeRequest();
			const response = await requestBuilder.makeRequest();

			expect( response ).to.have.status( 429 );
			assert.strictEqual( response.body.code, 'request-limit-reached' );
			assert.deepStrictEqual( response.body.context, { reason: 'temp-account-creation-limit-reached' } );
		} );

		describe( 'temp user authentication', () => {
			let existingTempUserName;
			let userSession;

			before( async () => {
				userSession = await action.getAnon();
				// Any edit works here. We just need an existing temp user for the actual test.
				const initialEdit = await withTempUsersEnabled( newRequestBuilder() )
					.withUser( userSession ).makeRequest();

				expect( initialEdit ).status.to.be.within( 200, 299 );
				const editMeta = await entityHelper.getLatestEditMetadata( initialEdit.body.id );
				existingTempUserName = editMeta.user;
			} );

			it( 'can authenticate as the temp user after the creation', async () => {
				const response = await withTempUsersEnabled( newRequestBuilder() )
					.withUser( userSession )
					.makeRequest();

				expect( response ).status.to.be.within( 200, 299 );
				const { user } = await entityHelper.getLatestEditMetadata( response.body.id );
				assert.include( user, tempUserPrefix );
				assert.strictEqual( user, existingTempUserName );
				assert.header( response, 'X-Authenticated-User', undefined );
				assert.header( response, 'X-Temporary-User-Created', undefined );
			} );
		} );
	} );
} );
