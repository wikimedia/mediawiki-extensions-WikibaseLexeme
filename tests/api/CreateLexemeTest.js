'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const {
	newCreateLexemeRequestBuilder,
	newGetLexemeRequestBuilder,
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { getLatestEditMetadata } = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes', () => {
	let languageId;
	let lexicalCategoryId;
	let stringPropertyId;
	let otherStringPropertyId;

	function newValidLexeme( fields = {} ) {
		return {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId,
			...fields
		};
	}

	before( async () => {
		languageId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		lexicalCategoryId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		stringPropertyId = ( await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `test-property-${ utils.uniq() }` }
		} ).makeRequest() ).body.id;
		otherStringPropertyId = ( await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `test-property-${ utils.uniq() }` }
		} ).makeRequest() ).body.id;
	} );

	it( 'returns the created lexeme and persists it', async () => {
		const lemma = `test-lemma-${ utils.uniq() }`;
		const statementValue = 'potato';
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: lemma },
			lexical_category: lexicalCategoryId,
			language: languageId,
			statements: {
				[ stringPropertyId ]: [ {
					property: { id: stringPropertyId },
					value: { type: 'value', content: statementValue }
				} ]
			}
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.match( response.body.id, /^L\d+$/ );
		assert.isTrue(
			new URL( response.header.location ).pathname.endsWith( `/entities/lexemes/${ response.body.id }` )
		);
		assert.deepStrictEqual( response.body.lemmas, { en: lemma } );
		assert.strictEqual( response.body.lexical_category, lexicalCategoryId );
		assert.strictEqual( response.body.language, languageId );

		assert.deepStrictEqual( Object.keys( response.body.statements ), [ stringPropertyId ] );
		const [ statement ] = response.body.statements[ stringPropertyId ];
		assert.strictEqual( statement.id.split( '$' )[ 0 ], response.body.id );
		assert.deepStrictEqual( statement, {
			id: statement.id,
			rank: 'normal',
			property: { id: stringPropertyId, data_type: 'string' },
			value: { type: 'value', content: statementValue },
			qualifiers: [],
			references: []
		} );

		const getLexemeResponse = await newGetLexemeRequestBuilder( response.body.id ).makeRequest();

		expect( getLexemeResponse ).to.have.status( 200 );
		assert.deepStrictEqual( getLexemeResponse.body, response.body );
		assert.match( response.header.etag, /^"\d+"$/ );
		assert.strictEqual( response.header.etag, getLexemeResponse.header.etag );
		assert.strictEqual(
			response.header[ 'last-modified' ],
			getLexemeResponse.header[ 'last-modified' ]
		);

		const editMetadata = await getLatestEditMetadata( response.body.id );
		assert.strictEqual( editMetadata.comment, '/* wbeditentity-create-lexeme:0| */' );
	} );

	[ 'lemmas', 'lexical_category', 'language' ].forEach( ( field ) => {
		it( `returns 400 if ${ field } is missing`, async () => {
			const lexeme = {
				lemmas: { en: `test-lemma-${ utils.uniq() }` },
				lexical_category: lexicalCategoryId,
				language: languageId
			};
			delete lexeme[ field ];

			const response = await newCreateLexemeRequestBuilder( lexeme ).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepStrictEqual( response.body.context, { path: '/lexeme', field } );
		} );
	} );

	it( 'returns 400 if the lexeme field is missing', async () => {
		const response = await newCreateLexemeRequestBuilder( {} )
			.withEmptyJsonBody()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'missing-field' );
		assert.deepStrictEqual( response.body.context, { path: '', field: 'lexeme' } );
	} );

	it( 'returns 400 if the lexeme field is not an object', async () => {
		const response = await newCreateLexemeRequestBuilder( 'potato' ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme' } );
	} );

	it( 'returns 400 if lemmas is empty', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: {},
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas' } );
	} );

	it( 'accepts a private use language code and trims the lemma text', async () => {
		const lemma = `test-lemma-${ utils.uniq() }`;
		const lemmaLanguage = `en-x-${ languageId }`;
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { [ lemmaLanguage ]: `  ${ lemma }  ` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.deepStrictEqual( response.body.lemmas, { [ lemmaLanguage ]: lemma } );

		const getLexemeResponse = await newGetLexemeRequestBuilder( response.body.id ).makeRequest();

		expect( getLexemeResponse ).to.have.status( 200 );
		assert.deepStrictEqual( getLexemeResponse.body.lemmas, { [ lemmaLanguage ]: lemma } );
	} );

	it( 'returns 400 if lemmas is not an object', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: [ 'potato' ],
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas' } );
	} );

	it( 'returns 400 if a lemma language code is invalid', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { 'invalid-language-code': `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-key' );
		assert.deepStrictEqual(
			response.body.context,
			{ path: '/lexeme/lemmas', key: 'invalid-language-code' }
		);
	} );

	it( 'returns 400 if a lemma text is invalid', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: '' },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas/en' } );
	} );

	[ 'lexical_category', 'language' ].forEach( ( field ) => {
		it( `returns 400 if ${ field } is not an item id`, async () => {
			const lexeme = {
				lemmas: { en: `test-lemma-${ utils.uniq() }` },
				lexical_category: lexicalCategoryId,
				language: languageId
			};
			lexeme[ field ] = 'potato';

			const response = await newCreateLexemeRequestBuilder( lexeme ).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepStrictEqual( response.body.context, { path: `/lexeme/${ field }` } );
		} );
	} );

	[ 'lexical_category', 'language' ].forEach( ( field ) => {
		it( `returns 400 if the ${ field } item does not exist`, async () => {
			const lexeme = {
				lemmas: { en: `test-lemma-${ utils.uniq() }` },
				lexical_category: lexicalCategoryId,
				language: languageId
			};
			lexeme[ field ] = 'Q999999999';

			const response = await newCreateLexemeRequestBuilder( lexeme ).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'referenced-resource-not-found' );
			assert.deepStrictEqual( response.body.context, { path: `/lexeme/${ field }` } );
		} );
	} );

	it( 'returns 400 if a lemma text is too long', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: 'x'.repeat( 1001 ) },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'value-too-long' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas/en', limit: 1000 } );
	} );

	it( 'ignores statement ids provided in the request', async () => {
		const response = await newCreateLexemeRequestBuilder( newValidLexeme( {
			statements: {
				[ stringPropertyId ]: [ {
					id: 'L1$00000000-0000-0000-0000-000000000000',
					property: { id: stringPropertyId },
					value: { type: 'value', content: 'potato' }
				} ]
			}
		} ) ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.strictEqual(
			response.body.statements[ stringPropertyId ][ 0 ].id.split( '$' )[ 0 ],
			response.body.id
		);
	} );

	[
		{
			name: 'statements not an object',
			statements: () => [ 'potato' ],
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: '/lexeme/statements' } )
		},
		{
			name: 'statement group not a list',
			statements: () => ( {
				[ stringPropertyId ]: { property: { id: stringPropertyId } }
			} ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/lexeme/statements/${ stringPropertyId }` } )
		},
		{
			name: 'statement not an object',
			statements: () => ( { [ stringPropertyId ]: [ 'potato' ] } ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/lexeme/statements/${ stringPropertyId }/0` } )
		},
		{
			name: 'statement rank invalid',
			statements: () => ( {
				[ stringPropertyId ]: [ {
					property: { id: stringPropertyId },
					value: { type: 'novalue' },
					rank: 'not-a-rank'
				} ]
			} ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/lexeme/statements/${ stringPropertyId }/0/rank` } )
		},
		{
			name: 'statement field missing',
			statements: () => ( {
				[ stringPropertyId ]: [ { property: { id: stringPropertyId } } ]
			} ),
			expectedCode: 'missing-field',
			expectedContext: () => ( {
				path: `/lexeme/statements/${ stringPropertyId }/0`,
				field: 'value'
			} )
		},
		{
			name: 'statement property does not exist',
			statements: () => ( {
				P999999999: [ {
					property: { id: 'P999999999' },
					value: { type: 'novalue' }
				} ]
			} ),
			expectedCode: 'referenced-resource-not-found',
			expectedContext: () => ( {
				path: '/lexeme/statements/P999999999/0/property/id'
			} )
		},
		{
			name: 'statement property id does not match the group key',
			statements: () => ( {
				[ stringPropertyId ]: [ {
					property: { id: otherStringPropertyId },
					value: { type: 'novalue' }
				} ]
			} ),
			expectedCode: 'statement-group-property-id-mismatch',
			expectedContext: () => ( {
				path: `/lexeme/statements/${ stringPropertyId }/0/property/id`,
				statement_group_property_id: stringPropertyId,
				statement_property_id: otherStringPropertyId
			} )
		}
	].forEach( ( { name, statements, expectedCode, expectedContext } ) => {
		it( `responds 400 - ${ name }`, async () => {
			const response = await newCreateLexemeRequestBuilder(
				newValidLexeme( { statements: statements() } )
			).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, expectedCode );
			assert.deepStrictEqual( response.body.context, expectedContext() );
		} );
	} );

	it( 'responds with a 400 error if the User-Agent header is empty', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} )
			.withHeader( 'user-agent', '' )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'missing-user-agent' );
		assert.include( response.body.message, 'User-Agent' );
	} );

	it( 'responds with an X-Authenticated-User header for a logged in user', async () => {
		const user = await action.alice();
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} )
			.withUser( user )
			.makeRequest();

		expect( response ).to.have.status( 201 );
		assert.header( response, 'X-Authenticated-User', user.username );
	} );

	it( 'can create a lexeme with edit metadata provided', async () => {
		const user = await action.robby();
		const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
		const editSummary = 'omg look i made an edit';

		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} )
			.withJsonBodyParam( 'tags', [ tag ] )
			.withJsonBodyParam( 'bot', true )
			.withJsonBodyParam( 'comment', editSummary )
			.withUser( user )
			.makeRequest();

		expect( response ).to.have.status( 201 );

		const editMetadata = await getLatestEditMetadata( response.body.id );
		assert.deepEqual( editMetadata.tags, [ tag ] );
		assert.property( editMetadata, 'bot' );
		assert.strictEqual(
			editMetadata.comment,
			`/* wbeditentity-create-lexeme:0| */ ${ editSummary }`
		);
		assert.strictEqual( editMetadata.user, user.username );
	} );

	it( 'returns 400 if an edit tag is invalid', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} )
			.withJsonBodyParam( 'tags', [ 'not-a-real-tag' ] )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/tags/0' } );
	} );

	it( 'returns 400 if the comment is too long', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} )
			.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'value-too-long' );
		assert.deepStrictEqual( response.body.context, { path: '/comment', limit: 500 } );
	} );

	describe( 'Authorization', () => {
		let root;

		before( async () => {
			root = await action.root();
		} );

		describe( 'blocked user', () => {
			let blockedUser;

			before( async () => {
				const username = utils.title( 'blocked-user-' );
				const password = utils.title( 'very-secret-' );
				await root.createAccount( { username, password } );
				blockedUser = action.getAnon();
				await blockedUser.login( username, password );
				await root.action( 'block', { user: username, reason: 'testing', token: await root.token() }, 'POST' );
			} );

			after( async () => {
				await root.action( 'unblock', { user: blockedUser.username, token: await root.token() }, 'POST' );
			} );

			it( 'returns 403 with the denial reason', async () => {
				const response = await newCreateLexemeRequestBuilder( {
					lemmas: { en: `test-lemma-${ utils.uniq() }` },
					lexical_category: lexicalCategoryId,
					language: languageId
				} )
					.withUser( blockedUser )
					.makeRequest();

				expect( response ).to.have.status( 403 );
				assert.header( response, 'Content-Language', 'en' );
				assert.strictEqual( response.body.code, 'permission-denied' );
				assert.deepStrictEqual( response.body.context, { denial_reason: 'blocked-user' } );
			} );
		} );

		it( 'returns 403 without the createpage right', async () => {
			const response = await newCreateLexemeRequestBuilder( {
				lemmas: { en: `test-lemma-${ utils.uniq() }` },
				lexical_category: lexicalCategoryId,
				language: languageId
			} )
				.withUser( await action.alice() )
				.withConfigOverride( 'wgGroupPermissions', {
					'*': { read: true, edit: true, createpage: false },
					user: { read: true, edit: true, createpage: false }
				} )
				.makeRequest();

			expect( response ).to.have.status( 403 );
			assert.strictEqual( response.body.error, 'rest-write-denied' );
		} );
	} );
} );
