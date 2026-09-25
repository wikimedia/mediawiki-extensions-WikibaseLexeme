'use strict';

const { assert, action, utils } = require( 'api-testing' );
const {
	newAddLexemeStatementRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const {
	createRedirectForLexeme,
	getItemId,
	getLatestEditMetadata,
	getStringPropertyId,
	newStatementWithRandomStringValue
} = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes/{lexeme_id}/statements', () => {
	let itemId;
	let lexemeId;
	let originalEtag;
	let originalLastModified;
	let stringPropertyId;

	before( async () => {
		itemId = await getItemId();
		const createLexemeResponse = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: itemId,
			language: itemId
		} ).makeRequest();
		lexemeId = createLexemeResponse.body.id;
		originalEtag = createLexemeResponse.header.etag;
		originalLastModified = new Date( createLexemeResponse.header[ 'last-modified' ] );
		stringPropertyId = await getStringPropertyId();

		// wait 1s so that the last modified timestamp of the next edit is different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	it( 'adds the statement', async () => {
		const statementValue = 'potato';
		const response = await newAddLexemeStatementRequestBuilder( lexemeId, {
			property: { id: stringPropertyId },
			value: { type: 'value', content: statementValue }
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.match( response.header.etag, /^"\d+"$/ );
		assert.notStrictEqual( response.header.etag, originalEtag );
		assert.isAbove(
			new Date( response.header[ 'last-modified' ] ),
			originalLastModified
		);
		assert.strictEqual( response.body.id.split( '$' )[ 0 ], lexemeId );
		assert.deepStrictEqual( response.body, {
			id: response.body.id,
			rank: 'normal',
			property: { id: stringPropertyId, data_type: 'string' },
			value: { type: 'value', content: statementValue },
			qualifiers: [],
			references: []
		} );

		const editMetadata = await getLatestEditMetadata( lexemeId );
		assert.strictEqual(
			editMetadata.comment,
			`/* wbsetclaim-create:1||1 */ [[Property:${ stringPropertyId }]]: ${ statementValue }`
		);
	} );

	it( 'can add a statement with edit metadata provided', async () => {
		const user = await action.robby();
		const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
		const editSummary = 'omg look i made an edit';
		const statement = newStatementWithRandomStringValue( stringPropertyId );

		const response = await newAddLexemeStatementRequestBuilder( lexemeId, statement )
			.withJsonBodyParam( 'tags', [ tag ] )
			.withJsonBodyParam( 'bot', true )
			.withJsonBodyParam( 'comment', editSummary )
			.withUser( user )
			.makeRequest();

		expect( response ).to.have.status( 201 );

		const editMetadata = await getLatestEditMetadata( lexemeId );
		assert.deepEqual( editMetadata.tags, [ tag ] );
		assert.property( editMetadata, 'bot' );
		assert.strictEqual(
			editMetadata.comment,
			`/* wbsetclaim-create:1||1 */ [[Property:${ stringPropertyId }]]: ` +
			`${ statement.value.content }, ${ editSummary }`
		);
		assert.strictEqual( editMetadata.user, user.username );
	} );

	it( 'returns 400 if the lexeme id is invalid', async () => {
		const response = await newAddLexemeStatementRequestBuilder(
			'not-a-lexeme-id',
			newStatementWithRandomStringValue( stringPropertyId )
		).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-path-parameter' );
		assert.deepStrictEqual( response.body.context, { parameter: 'lexeme_id' } );
	} );

	[
		{
			name: 'statement rank invalid',
			statement: () => ( {
				property: { id: stringPropertyId },
				value: { type: 'novalue' },
				rank: 'not-a-rank'
			} ),
			expectedCode: 'invalid-value',
			expectedContext: { path: '/statement/rank' }
		},
		{
			name: 'statement field missing',
			statement: () => ( { property: { id: stringPropertyId } } ),
			expectedCode: 'missing-field',
			expectedContext: { path: '/statement', field: 'value' }
		},
		{
			name: 'statement property does not exist',
			statement: () => ( {
				property: { id: 'P999999999' },
				value: { type: 'novalue' }
			} ),
			expectedCode: 'referenced-resource-not-found',
			expectedContext: { path: '/statement/property/id' }
		}
	].forEach( ( { name, statement, expectedCode, expectedContext } ) => {
		it( `returns 400 - ${ name }`, async () => {
			const response = await newAddLexemeStatementRequestBuilder( lexemeId, statement() ).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, expectedCode );
			assert.deepStrictEqual( response.body.context, expectedContext );
		} );
	} );

	it( 'returns 400 if an edit tag is invalid', async () => {
		const response = await newAddLexemeStatementRequestBuilder(
			lexemeId,
			newStatementWithRandomStringValue( stringPropertyId )
		)
			.withJsonBodyParam( 'tags', [ 'not-a-real-tag' ] )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/tags/0' } );
	} );

	it( 'returns 400 if the comment is too long', async () => {
		const response = await newAddLexemeStatementRequestBuilder(
			lexemeId,
			newStatementWithRandomStringValue( stringPropertyId )
		)
			.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'value-too-long' );
		assert.deepStrictEqual( response.body.context, { path: '/comment', limit: 500 } );
	} );

	it( 'returns 404 if the lexeme does not exist', async () => {
		const response = await newAddLexemeStatementRequestBuilder(
			'L999999',
			newStatementWithRandomStringValue( stringPropertyId )
		).makeRequest();

		expect( response ).to.have.status( 404 );
		assert.strictEqual( response.body.code, 'resource-not-found' );
		assert.deepStrictEqual( response.body.context, { resource_type: 'lexeme' } );
	} );

	it( 'returns 409 if the lexeme has been redirected', async () => {
		const sourceLexemeResponse = await newCreateLexemeRequestBuilder( {
			lemmas: { 'en-ca': `redirect-${ utils.uniq() }` },
			lexical_category: itemId,
			language: itemId
		} ).makeRequest();

		const sourceLexemeId = sourceLexemeResponse.body.id;

		await createRedirectForLexeme(
			sourceLexemeId,
			lexemeId
		);

		const response = await newAddLexemeStatementRequestBuilder(
			sourceLexemeId,
			{
				property: { id: stringPropertyId },
				value: { type: 'value', content: 'potato' }
			}
		).makeRequest();

		expect( response ).to.have.status( 409 );

		assert.deepStrictEqual( response.body, {
			code: 'redirected-lexeme',
			message: `Lexeme ${ sourceLexemeId } has been redirected to ${ lexemeId }.`,
			context: {
				redirect_target: lexemeId
			}
		} );
	} );
} );
