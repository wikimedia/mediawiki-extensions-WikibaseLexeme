'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newCreateLexemeRequestBuilder,
	newGetLexemeRequestBuilder,
	newPatchLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const { assertValidError } = require( './helpers/responseValidator' );
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

	describe( 'patch document validation', () => {
		it( 'invalid patch', async () => {
			const response = await newPatchLexemeRequestBuilder(
				lexemeId,
				{ foo: 'this is not a valid JSON Patch' }
			).makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch' } );
			assert.include( response.body.message, '/patch' );
		} );

		it( 'missing top-level field', async () => {
			const response = await newPatchLexemeRequestBuilder( lexemeId, [] )
				.withEmptyJsonBody()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepStrictEqual( response.body.context, { path: '', field: 'patch' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - missing 'op' field", async () => {
			const response = await newPatchLexemeRequestBuilder(
				lexemeId,
				[ { path: '/a/b/c', value: 'test' } ]
			).makeRequest();

			assertValidError( response, 400, 'missing-field', { path: '/patch/0', field: 'op' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - missing 'path' field", async () => {
			const response = await newPatchLexemeRequestBuilder( lexemeId, [ { op: 'remove' } ] ).makeRequest();

			assertValidError( response, 400, 'missing-field', { path: '/patch/0', field: 'path' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - missing 'value' field", async () => {
			const response = await newPatchLexemeRequestBuilder(
				lexemeId,
				[ { op: 'add', path: '/a/b/c' } ]
			).makeRequest();

			assertValidError( response, 400, 'missing-field', { path: '/patch/0', field: 'value' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - missing 'from' field", async () => {
			const response = await newPatchLexemeRequestBuilder(
				lexemeId,
				[ { op: 'move', path: '/a/b/c' } ]
			).makeRequest();

			assertValidError( response, 400, 'missing-field', { path: '/patch/0', field: 'from' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - invalid 'op' field", async () => {
			const response = await newPatchLexemeRequestBuilder(
				lexemeId,
				[ { op: 'foobar', path: '/a/b/c', value: 'test' } ]
			).makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch/0/op' } );
			assert.include( response.body.message, '/patch/0/op' );
		} );

		it( "invalid patch - 'op' is not a string", async () => {
			const response = await newPatchLexemeRequestBuilder(
				lexemeId,
				[ { op: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c', value: 'test' } ]
			).makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch/0/op' } );
			assert.include( response.body.message, '/patch/0/op' );
		} );

		it( "invalid patch - 'path' is not a string", async () => {
			const response = await newPatchLexemeRequestBuilder(
				lexemeId,
				[ { op: 'add', path: { foo: [ 'bar' ], baz: 42 }, value: 'test' } ]
			).makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch/0/path' } );
			assert.include( response.body.message, '/patch/0/path' );
		} );

		it( "invalid patch - 'from' is not a string", async () => {
			const response = await newPatchLexemeRequestBuilder(
				lexemeId,
				[ { op: 'move', from: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c' } ]
			).makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch/0/from' } );
			assert.include( response.body.message, '/patch/0/from' );
		} );
	} );

} );
