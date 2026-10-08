'use strict';

const { assert, action, utils } = require( 'api-testing' );
const {
	newAddLexemeFormRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const {
	createRedirectForLexeme,
	getItemId,
	getLatestEditMetadata,
	getOtherStringPropertyId,
	getStringPropertyId
} = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes/{lexeme_id}/forms', () => {
	let lexemeId;
	let grammaticalFeatureId;
	let stringPropertyId;
	let otherStringPropertyId;
	let originalEtag;
	let originalLastModified;
	let itemId;

	function newValidForm( fields = {} ) {
		return {
			representations: { en: `test-representation-${ utils.uniq() }` },
			...fields
		};
	}

	before( async () => {
		itemId = await getItemId();
		grammaticalFeatureId = await getItemId();
		stringPropertyId = await getStringPropertyId();
		otherStringPropertyId = await getOtherStringPropertyId();
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

		const editMetadata = await getLatestEditMetadata( lexemeId );
		assert.strictEqual(
			editMetadata.comment,
			`/* add-form:1||${ formId } */ ${ representation }`
		);
	} );

	it( 'can add a form with edit metadata provided', async () => {
		const user = await action.robby();
		const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
		const editSummary = 'omg look i made an edit';
		const form = newValidForm();

		const response = await newAddLexemeFormRequestBuilder( lexemeId, form )
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
			`/* add-form:1||${ response.body.id } */ ${ form.representations.en }, ${ editSummary }`
		);
		assert.strictEqual( editMetadata.user, user.username );
	} );

	it( 'accepts a language code containing an item ID and trims the representation text', async () => {
		const representation = `test-representation-${ utils.uniq() }`;
		const representationLanguage = `en-x-${ itemId }`;
		const response = await newAddLexemeFormRequestBuilder( lexemeId, {
			representations: { [ representationLanguage ]: `  ${ representation }  ` }
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.deepStrictEqual( response.body.representations, { [ representationLanguage ]: representation } );
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

	[
		{
			name: 'representations missing',
			form: () => ( {} ),
			expectedCode: 'missing-field',
			expectedContext: { path: '/form', field: 'representations' }
		},
		{
			name: 'representations not an object',
			form: () => ( { representations: [ 'potatoes' ] } ),
			expectedCode: 'invalid-value',
			expectedContext: { path: '/form/representations' }
		},
		{
			name: 'representations empty',
			form: () => ( { representations: {} } ),
			expectedCode: 'invalid-value',
			expectedContext: { path: '/form/representations' }
		},
		{
			name: 'representation language code invalid',
			form: () => ( { representations: { 'invalid-language-code': 'potatoes' } } ),
			expectedCode: 'invalid-key',
			expectedContext: { path: '/form/representations', key: 'invalid-language-code' }
		},
		{
			name: 'representation text invalid',
			form: () => ( { representations: { en: '' } } ),
			expectedCode: 'invalid-value',
			expectedContext: { path: '/form/representations/en' }
		},
		{
			name: 'representation text too long',
			form: () => ( { representations: { en: 'x'.repeat( 1001 ) } } ),
			expectedCode: 'value-too-long',
			expectedContext: { path: '/form/representations/en', limit: 1000 }
		}
	].forEach( ( { name, form, expectedCode, expectedContext } ) => {
		it( `responds 400 - ${ name }`, async () => {
			const response = await newAddLexemeFormRequestBuilder( lexemeId, form() ).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, expectedCode );
			assert.deepStrictEqual( response.body.context, expectedContext );
		} );
	} );

	it( "returns 400 if 'grammatical_features' is not an array", async () => {
		const response = await newAddLexemeFormRequestBuilder(
			lexemeId,
			newValidForm( { grammatical_features: 'Q1' } )
		).makeRequest();
		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/form/grammatical_features' } );
	} );

	it( "returns 400 if a 'grammatical_features' element is not an item id", async () => {
		const response = await newAddLexemeFormRequestBuilder(
			lexemeId,
			newValidForm( { grammatical_features: [ grammaticalFeatureId, 'X1' ] } )
		).makeRequest();
		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/form/grammatical_features/1' } );
	} );

	it( "returns 400 if a 'grammatical_features' item does not exist", async () => {
		const response = await newAddLexemeFormRequestBuilder(
			lexemeId,
			newValidForm( { grammatical_features: [ 'Q999999999' ] } )
		).makeRequest();
		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'referenced-resource-not-found' );
		assert.deepStrictEqual( response.body.context, { path: '/form/grammatical_features/0' } );
	} );

	it( 'responds 400 if the lexeme id is invalid', async () => {
		const response = await newAddLexemeFormRequestBuilder( 'not-a-lexeme-id', newValidForm() ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-path-parameter' );
		assert.deepStrictEqual( response.body.context, { parameter: 'lexeme_id' } );
	} );

	it( 'responds 404 if the lexeme does not exist', async () => {
		const response = await newAddLexemeFormRequestBuilder( 'L999999', newValidForm() ).makeRequest();

		expect( response ).to.have.status( 404 );
		assert.strictEqual( response.body.code, 'resource-not-found' );
		assert.deepStrictEqual( response.body.context, { resource_type: 'lexeme' } );
	} );

	it( 'returns 409 if the lexeme has been redirected', async () => {
		const sourceLexemeResponse = await newCreateLexemeRequestBuilder( {
			lemmas: { 'en-ca': `redirect-${ utils.uniq() }` },
			lexical_category: itemId,
			language: itemId
		} ).makeRequest();

		const sourceLexemeId = sourceLexemeResponse.body.id;

		await createRedirectForLexeme(
			sourceLexemeId,
			lexemeId
		);

		const response = await newAddLexemeFormRequestBuilder( sourceLexemeId, newValidForm() ).makeRequest();
		expect( response ).to.have.status( 409 );

		assert.deepStrictEqual( response.body, {
			code: 'redirected-lexeme',
			message: `Lexeme ${ sourceLexemeId } has been redirected to ${ lexemeId }.`,
			context: {
				redirect_target: lexemeId
			}
		} );
	} );
} );
