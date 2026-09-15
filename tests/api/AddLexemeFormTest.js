'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newAddLexemeFormRequestBuilder,
	newCreateLexemeRequestBuilder,
	newCreateItemRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );

describe( 'POST /entities/lexemes/{lexeme_id}/forms', () => {
	let lexemeId;
	let grammaticalFeatureId;
	let originalEtag;
	let originalLastModified;

	before( async () => {
		const itemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		grammaticalFeatureId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		const createLexemeResponse = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: itemId,
			language: itemId
		} ).makeRequest();
		lexemeId = createLexemeResponse.body.id;
		originalEtag = createLexemeResponse.header.etag;
		originalLastModified = new Date( createLexemeResponse.header[ 'last-modified' ] );

		// wait 1s so that the last modified timestamp of the next edit is different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	it( 'adds the form', async () => {
		const representation = 'potatoes';
		const response = await newAddLexemeFormRequestBuilder( lexemeId, {
			representations: { en: representation },
			grammatical_features: [ grammaticalFeatureId ]
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.match( response.header.etag, /^"\d+"$/ );
		assert.notStrictEqual( response.header.etag, originalEtag );
		assert.isAbove(
			new Date( response.header[ 'last-modified' ] ),
			originalLastModified
		);
		assert.deepStrictEqual( response.body, {
			id: `${ lexemeId }-F1`,
			representations: { en: representation },
			grammatical_features: [ grammaticalFeatureId ],
			statements: {}
		} );
	} );
} );
