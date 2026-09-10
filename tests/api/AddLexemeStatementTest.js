'use strict';

const { assert, action, utils } = require( 'api-testing' );
const {
	newAddLexemeStatementRequestBuilder,
	newCreateLexemeRequestBuilder,
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const { getLatestEditMetadata, newStatementWithRandomStringValue } = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes/{lexeme_id}/statements', () => {
	let lexemeId;
	let originalEtag;
	let originalLastModified;
	let stringPropertyId;

	before( async () => {
		const itemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		const createLexemeResponse = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: itemId,
			language: itemId
		} ).makeRequest();
		lexemeId = createLexemeResponse.body.id;
		originalEtag = createLexemeResponse.header.etag;
		originalLastModified = new Date( createLexemeResponse.header[ 'last-modified' ] );
		stringPropertyId = ( await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `test-property-${ utils.uniq() }` }
		} ).makeRequest() ).body.id;

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
} );
