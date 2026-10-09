'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newAddLexemeSenseRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const { getItemId } = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes/{lexeme_id}/senses', () => {
	let itemId;
	let lexemeId;
	let originalEtag;
	let originalLastModified;

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

	it( 'accepts a language code containing an item ID and trims the gloss text', async () => {
		const gloss = `test-gloss-${ utils.uniq() }`;
		const glossLanguage = `en-x-${ itemId }`;
		const response = await newAddLexemeSenseRequestBuilder( lexemeId, {
			glosses: { [ glossLanguage ]: `  ${ gloss }  ` }
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.deepStrictEqual( response.body.glosses, { [ glossLanguage ]: gloss } );
	} );

	[
		{
			name: 'glosses missing',
			sense: () => ( {} ),
			expectedCode: 'missing-field',
			expectedContext: { path: '/sense', field: 'glosses' }
		},
		{
			name: 'glosses not an object',
			sense: () => ( { glosses: [ 'a starchy tuber' ] } ),
			expectedCode: 'invalid-value',
			expectedContext: { path: '/sense/glosses' }
		},
		{
			name: 'glosses empty',
			sense: () => ( { glosses: {} } ),
			expectedCode: 'invalid-value',
			expectedContext: { path: '/sense/glosses' }
		},
		{
			name: 'gloss language code invalid',
			sense: () => ( { glosses: { 'invalid-language-code': 'a starchy tuber' } } ),
			expectedCode: 'invalid-key',
			expectedContext: { path: '/sense/glosses', key: 'invalid-language-code' }
		},
		{
			name: 'gloss text invalid',
			sense: () => ( { glosses: { en: '' } } ),
			expectedCode: 'invalid-value',
			expectedContext: { path: '/sense/glosses/en' }
		},
		{
			name: 'gloss text too long',
			sense: () => ( { glosses: { en: 'x'.repeat( 1001 ) } } ),
			expectedCode: 'value-too-long',
			expectedContext: { path: '/sense/glosses/en', limit: 1000 }
		}
	].forEach( ( { name, sense, expectedCode, expectedContext } ) => {
		it( `responds 400 - ${ name }`, async () => {
			const response = await newAddLexemeSenseRequestBuilder( lexemeId, sense() ).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, expectedCode );
			assert.deepStrictEqual( response.body.context, expectedContext );
		} );
	} );

	it( 'responds 400 if the lexeme id is invalid', async () => {
		const response = await newAddLexemeSenseRequestBuilder(
			'not-a-lexeme-id',
			{ glosses: { en: 'a starchy tuber' } }
		).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-path-parameter' );
		assert.deepStrictEqual( response.body.context, { parameter: 'lexeme_id' } );
	} );
} );
