'use strict';

const { assert, utils } = require( 'api-testing' );
const {
	newAddLexemeFormRequestBuilder,
	newCreateLexemeRequestBuilder,
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );

describe( 'POST /entities/lexemes/{lexeme_id}/forms', () => {
	let lexemeId;
	let grammaticalFeatureId;
	let stringPropertyId;
	let otherStringPropertyId;
	let originalEtag;
	let originalLastModified;

	function newValidForm( fields = {} ) {
		return {
			representations: { en: `test-representation-${ utils.uniq() }` },
			...fields
		};
	}

	before( async () => {
		const itemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		grammaticalFeatureId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		stringPropertyId = ( await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `test-property-${ utils.uniq() }` }
		} ).makeRequest() ).body.id;
		otherStringPropertyId = ( await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `test-property-${ utils.uniq() }` }
		} ).makeRequest() ).body.id;
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
		const statementValue = 'potato';
		const response = await newAddLexemeFormRequestBuilder( lexemeId, {
			representations: { en: representation },
			grammatical_features: [ grammaticalFeatureId ],
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

		const formId = `${ lexemeId }-F1`;
		const [ statement ] = response.body.statements[ stringPropertyId ];
		assert.strictEqual( statement.id.split( '$' )[ 0 ], formId );
		assert.deepStrictEqual( response.body, {
			id: formId,
			representations: { en: representation },
			grammatical_features: [ grammaticalFeatureId ],
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

	it( 'ignores statement ids provided in the request', async () => {
		const statementIdSuffix = '00000000-0000-0000-0000-000000000000';
		const response = await newAddLexemeFormRequestBuilder( lexemeId, newValidForm( {
			statements: {
				[ stringPropertyId ]: [ {
					id: `${ lexemeId }-F1$${ statementIdSuffix }`,
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
			expectedContext: () => ( { path: '/form/statements' } )
		},
		{
			name: 'statement group not a list',
			statements: () => ( {
				[ stringPropertyId ]: { property: { id: stringPropertyId } }
			} ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/form/statements/${ stringPropertyId }` } )
		},
		{
			name: 'statement not an object',
			statements: () => ( { [ stringPropertyId ]: [ 'potato' ] } ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/form/statements/${ stringPropertyId }/0` } )
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
			expectedContext: () => ( { path: `/form/statements/${ stringPropertyId }/0/rank` } )
		},
		{
			name: 'statement field missing',
			statements: () => ( {
				[ stringPropertyId ]: [ { property: { id: stringPropertyId } } ]
			} ),
			expectedCode: 'missing-field',
			expectedContext: () => ( {
				path: `/form/statements/${ stringPropertyId }/0`,
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
				path: '/form/statements/P999999999/0/property/id'
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
				path: `/form/statements/${ stringPropertyId }/0/property/id`,
				statement_group_property_id: stringPropertyId,
				statement_property_id: otherStringPropertyId
			} )
		}
	].forEach( ( { name, statements, expectedCode, expectedContext } ) => {
		it( `responds 400 - ${ name }`, async () => {
			const response = await newAddLexemeFormRequestBuilder(
				lexemeId,
				newValidForm( { statements: statements() } )
			).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, expectedCode );
			assert.deepStrictEqual( response.body.context, expectedContext() );
		} );
	} );
} );
