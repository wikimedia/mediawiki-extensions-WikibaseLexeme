/* eslint-env mocha */
'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newAddLexemeStatementRequestBuilder,
	newCreateLexemeRequestBuilder,
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );

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
	} );
} );
