'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newAddLexemeSenseRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const {
	getItemId,
	getOtherStringPropertyId,
	getStringPropertyId
} = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes/{lexeme_id}/senses', () => {
	let itemId;
	let lexemeId;
	let stringPropertyId;
	let otherStringPropertyId;
	let originalEtag;
	let originalLastModified;

	function newValidSense( fields = {} ) {
		return {
			glosses: { en: `test-gloss-${ utils.uniq() }` },
			...fields
		};
	}

	before( async () => {
		stringPropertyId = await getStringPropertyId();
		otherStringPropertyId = await getOtherStringPropertyId();
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
		const statementValue = 'potato';
		const response = await newAddLexemeSenseRequestBuilder( lexemeId, {
			glosses: { en: gloss },
			statements: {
				[ stringPropertyId ]: [ {
					property: { id: stringPropertyId },
					value: { type: 'value', content: statementValue }
				} ]
			}
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.match( response.header.etag, /^"\d+"$/ );
		assert.notStrictEqual( response.header.etag, originalEtag );
		assert.isAbove(
			new Date( response.header[ 'last-modified' ] ),
			originalLastModified
		);

		const senseId = `${ lexemeId }-S1`;
		const [ statement ] = response.body.statements[ stringPropertyId ];
		assert.strictEqual( statement.id.split( '$' )[ 0 ], senseId );
		assert.deepStrictEqual( response.body, {
			id: `${ lexemeId }-S1`,
			glosses: { en: gloss },
			statements: {
				[ stringPropertyId ]: [ {
					id: statement.id,
					rank: 'normal',
					property: { id: stringPropertyId, data_type: 'string' },
					value: { type: 'value', content: statementValue },
					qualifiers: [],
					references: []
				} ]
			}

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

	it( 'ignores statement ids provided in the request', async () => {
		const statementIdSuffix = '00000000-0000-0000-0000-000000000000';
		const response = await newAddLexemeSenseRequestBuilder( lexemeId, newValidSense( {
			statements: {
				[ stringPropertyId ]: [ {
					id: `${ lexemeId }-S1$${ statementIdSuffix }`,
					property: { id: stringPropertyId },
					value: { type: 'value', content: 'potato' }
				} ]
			}
		} ) ).makeRequest();

		expect( response ).to.have.status( 201 );
		const statementParts = response.body.statements[ stringPropertyId ][ 0 ].id.split( '$' );
		assert.strictEqual( statementParts[ 0 ], response.body.id );
		assert.notStrictEqual( statementParts[ 1 ], statementIdSuffix );
	} );

	[
		{
			name: 'statements not an object',
			statements: () => [ 'potato' ],
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: '/sense/statements' } )
		},
		{
			name: 'statement group not a list',
			statements: () => ( {
				[ stringPropertyId ]: { property: { id: stringPropertyId } }
			} ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/sense/statements/${ stringPropertyId }` } )
		},
		{
			name: 'statement not an object',
			statements: () => ( { [ stringPropertyId ]: [ 'potato' ] } ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/sense/statements/${ stringPropertyId }/0` } )
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
			expectedContext: () => ( { path: `/sense/statements/${ stringPropertyId }/0/rank` } )
		},
		{
			name: 'statement field missing',
			statements: () => ( {
				[ stringPropertyId ]: [ { property: { id: stringPropertyId } } ]
			} ),
			expectedCode: 'missing-field',
			expectedContext: () => ( {
				path: `/sense/statements/${ stringPropertyId }/0`,
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
				path: '/sense/statements/P999999999/0/property/id'
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
				path: `/sense/statements/${ stringPropertyId }/0/property/id`,
				statement_group_property_id: stringPropertyId,
				statement_property_id: otherStringPropertyId
			} )
		}
	].forEach( ( { name, statements, expectedCode, expectedContext } ) => {
		it( `responds 400 - ${ name }`, async () => {
			const response = await newAddLexemeSenseRequestBuilder(
				lexemeId,
				newValidSense( { statements: statements() } )
			).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, expectedCode );
			assert.deepStrictEqual( response.body.context, expectedContext() );
		} );
	} );
} );
