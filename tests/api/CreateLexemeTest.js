/* eslint-env mocha */
'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newCreateLexemeRequestBuilder,
	newGetLexemeRequestBuilder,
	newCreateItemRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { getLatestEditMetadata } = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes', () => {
	let languageId;
	let lexicalCategoryId;

	before( async () => {
		languageId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		lexicalCategoryId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
	} );

	it( 'returns the created lexeme and persists it', async () => {
		const lemma = `test-lemma-${ utils.uniq() }`;
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: lemma },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		assert.strictEqual( response.status, 201, response.text );
		assert.match( response.body.id, /^L\d+$/ );
		assert.deepStrictEqual( response.body.lemmas, { en: lemma } );
		assert.strictEqual( response.body.lexical_category, lexicalCategoryId );
		assert.strictEqual( response.body.language, languageId );

		const getLexemeResponse = await newGetLexemeRequestBuilder( response.body.id ).makeRequest();

		assert.strictEqual( getLexemeResponse.status, 200, getLexemeResponse.text );
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

			assert.strictEqual( response.status, 400, response.text );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepStrictEqual( response.body.context, { path: '/lexeme', field } );
		} );
	} );

	it( 'returns 400 if lemmas is empty', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: {},
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		assert.strictEqual( response.status, 400, response.text );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas' } );
	} );

	it( 'accepts a private use language code and trims the lemma text', async () => {
		const lemma = `test-lemma-${ utils.uniq() }`;
		const lemmaLanguage = `en-x-${ languageId }`;
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { [ lemmaLanguage ]: `  ${ lemma }  ` },
			// eslint-disable-next-line camelcase
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		assert.strictEqual( response.status, 201, response.text );
		assert.deepStrictEqual( response.body.lemmas, { [ lemmaLanguage ]: lemma } );

		const getLexemeResponse = await newGetLexemeRequestBuilder( response.body.id ).makeRequest();

		assert.strictEqual( getLexemeResponse.status, 200, getLexemeResponse.text );
		assert.deepStrictEqual( getLexemeResponse.body.lemmas, { [ lemmaLanguage ]: lemma } );
	} );

	it( 'returns 400 if lemmas is not an object', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: [ 'potato' ],
			// eslint-disable-next-line camelcase
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		assert.strictEqual( response.status, 400, response.text );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas' } );
	} );

	it( 'returns 400 if a lemma language code is invalid', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { 'invalid-language-code': `test-lemma-${ utils.uniq() }` },
			// eslint-disable-next-line camelcase
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		assert.strictEqual( response.status, 400, response.text );
		assert.strictEqual( response.body.code, 'invalid-key' );
		assert.deepStrictEqual(
			response.body.context,
			{ path: '/lexeme/lemmas', key: 'invalid-language-code' }
		);
	} );

	it( 'returns 400 if a lemma text is invalid', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: '' },
			// eslint-disable-next-line camelcase
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		assert.strictEqual( response.status, 400, response.text );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas/en' } );
	} );

	it( 'returns 400 if a lemma text is too long', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: 'x'.repeat( 1001 ) },
			// eslint-disable-next-line camelcase
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		assert.strictEqual( response.status, 400, response.text );
		assert.strictEqual( response.body.code, 'value-too-long' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas/en', limit: 1000 } );
	} );
} );
