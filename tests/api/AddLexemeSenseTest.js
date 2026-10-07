'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newAddLexemeSenseRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const { getItemId } = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes/{lexeme_id}/senses', () => {
	let lexemeId;
	let originalEtag;
	let originalLastModified;

	before( async () => {
		const itemId = await getItemId();
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

	it( 'adds the sense', async () => {
		const gloss = 'a starchy tuber';
		const response = await newAddLexemeSenseRequestBuilder( lexemeId, {
			glosses: { en: gloss }
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.match( response.header.etag, /^"\d+"$/ );
		assert.notStrictEqual( response.header.etag, originalEtag );
		assert.isAbove(
			new Date( response.header[ 'last-modified' ] ),
			originalLastModified
		);
		assert.deepStrictEqual( response.body, {
			id: `${ lexemeId }-S1`,
			glosses: { en: gloss },
			statements: {}
		} );
	} );
} );
