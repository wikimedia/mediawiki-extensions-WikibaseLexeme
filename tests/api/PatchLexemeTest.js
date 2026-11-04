'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newCreateLexemeRequestBuilder,
	newGetLexemeRequestBuilder,
	newPatchLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const testValidatesPatch = require( '../../../Wikibase/repo/domains/crud/tests/mocha/helpers/testValidatesPatch' );
const { getItemId } = require( './helpers/entityHelper' );

describe( 'PATCH /entities/lexemes/{lexeme_id}', () => {
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

	it( 'patches the lemmas', async () => {
		const newLemma = `test-lemma-${ utils.uniq() }`;
		const otherNewLemma = `test-lemma-${ utils.uniq() }`;

		const response = await newPatchLexemeRequestBuilder( lexemeId, [
			{ op: 'replace', path: '/lemmas/en', value: newLemma },
			{ op: 'add', path: '/lemmas/en-gb', value: otherNewLemma }
		] ).makeRequest();

		expect( response ).to.have.status( 200 );
		assert.match( response.header.etag, /^"\d+"$/ );
		assert.notStrictEqual( response.header.etag, originalEtag );
		assert.isAbove(
			new Date( response.header[ 'last-modified' ] ),
			originalLastModified
		);
		const expectedLemmas = { en: newLemma, 'en-gb': otherNewLemma };
		assert.deepStrictEqual( response.body.lemmas, expectedLemmas );

		const getResponse = await newGetLexemeRequestBuilder( lexemeId ).makeRequest();
		assert.deepStrictEqual( getResponse.body.lemmas, expectedLemmas );
	} );

	it( 'responds 400 if the lexeme id is invalid', async () => {
		const response = await newPatchLexemeRequestBuilder( 'not-a-lexeme-id', [
			{ op: 'add', path: '/lemmas/en-gb', value: `test-lemma-${ utils.uniq() }` }
		] ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-path-parameter' );
		assert.deepStrictEqual( response.body.context, { parameter: 'lexeme_id' } );
	} );

	testValidatesPatch( ( patch ) => newPatchLexemeRequestBuilder( lexemeId, patch ) );

} );
