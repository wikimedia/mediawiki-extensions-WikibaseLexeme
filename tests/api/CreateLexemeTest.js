'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const {
	newCreateLexemeRequestBuilder,
	newGetLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const {
	getItemId,
	getLatestEditMetadata,
	getOtherStringPropertyId,
	getStringPropertyId
} = require( './helpers/entityHelper' );

describe( 'POST /entities/lexemes', () => {
	let languageId;
	let lexicalCategoryId;
	let stringPropertyId;
	let otherStringPropertyId;

	function newValidLexeme( fields = {} ) {
		return {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId,
			...fields
		};
	}

	before( async () => {
		languageId = await getItemId();
		lexicalCategoryId = await getItemId();
		stringPropertyId = await getStringPropertyId();
		otherStringPropertyId = await getOtherStringPropertyId();
	} );

	it( 'returns the created lexeme and persists it', async () => {
		const lemma = `test-lemma-${ utils.uniq() }`;
		const statementValue = 'potato';
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: lemma },
			lexical_category: lexicalCategoryId,
			language: languageId,
			statements: {
				[ stringPropertyId ]: [ {
					property: { id: stringPropertyId },
					value: { type: 'value', content: statementValue }
				} ]
			}
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.match( response.body.id, /^L\d+$/ );
		assert.isTrue(
			new URL( response.header.location ).pathname.endsWith( `/entities/lexemes/${ response.body.id }` )
		);
		assert.deepStrictEqual( response.body.lemmas, { en: lemma } );
		assert.strictEqual( response.body.lexical_category, lexicalCategoryId );
		assert.strictEqual( response.body.language, languageId );

		assert.deepStrictEqual( Object.keys( response.body.statements ), [ stringPropertyId ] );
		const [ statement ] = response.body.statements[ stringPropertyId ];
		assert.strictEqual( statement.id.split( '$' )[ 0 ], response.body.id );
		assert.deepStrictEqual( statement, {
			id: statement.id,
			rank: 'normal',
			property: { id: stringPropertyId, data_type: 'string' },
			value: { type: 'value', content: statementValue },
			qualifiers: [],
			references: []
		} );

		const getLexemeResponse = await newGetLexemeRequestBuilder( response.body.id ).makeRequest();

		expect( getLexemeResponse ).to.have.status( 200 );
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

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepStrictEqual( response.body.context, { path: '/lexeme', field } );
		} );
	} );

	it( 'returns 400 if the lexeme field is missing', async () => {
		const response = await newCreateLexemeRequestBuilder( {} )
			.withEmptyJsonBody()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'missing-field' );
		assert.deepStrictEqual( response.body.context, { path: '', field: 'lexeme' } );
	} );

	it( 'returns 400 if the lexeme field is not an object', async () => {
		const response = await newCreateLexemeRequestBuilder( 'potato' ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme' } );
	} );

	it( 'returns 400 if lemmas is empty', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: {},
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas' } );
	} );

	it( 'accepts a private use language code and trims the lemma text', async () => {
		const lemma = `test-lemma-${ utils.uniq() }`;
		const lemmaLanguage = `en-x-${ languageId }`;
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { [ lemmaLanguage ]: `  ${ lemma }  ` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.deepStrictEqual( response.body.lemmas, { [ lemmaLanguage ]: lemma } );

		const getLexemeResponse = await newGetLexemeRequestBuilder( response.body.id ).makeRequest();

		expect( getLexemeResponse ).to.have.status( 200 );
		assert.deepStrictEqual( getLexemeResponse.body.lemmas, { [ lemmaLanguage ]: lemma } );
	} );

	it( 'returns 400 if lemmas is not an object', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: [ 'potato' ],
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas' } );
	} );

	it( 'returns 400 if a lemma language code is invalid', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { 'invalid-language-code': `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-key' );
		assert.deepStrictEqual(
			response.body.context,
			{ path: '/lexeme/lemmas', key: 'invalid-language-code' }
		);
	} );

	it( 'returns 400 if a lemma text is invalid', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: '' },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas/en' } );
	} );

	[ 'lexical_category', 'language' ].forEach( ( field ) => {
		it( `returns 400 if ${ field } is not an item id`, async () => {
			const lexeme = {
				lemmas: { en: `test-lemma-${ utils.uniq() }` },
				lexical_category: lexicalCategoryId,
				language: languageId
			};
			lexeme[ field ] = 'potato';

			const response = await newCreateLexemeRequestBuilder( lexeme ).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepStrictEqual( response.body.context, { path: `/lexeme/${ field }` } );
		} );
	} );

	[ 'lexical_category', 'language' ].forEach( ( field ) => {
		it( `returns 400 if the ${ field } item does not exist`, async () => {
			const lexeme = {
				lemmas: { en: `test-lemma-${ utils.uniq() }` },
				lexical_category: lexicalCategoryId,
				language: languageId
			};
			lexeme[ field ] = 'Q999999999';

			const response = await newCreateLexemeRequestBuilder( lexeme ).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'referenced-resource-not-found' );
			assert.deepStrictEqual( response.body.context, { path: `/lexeme/${ field }` } );
		} );
	} );

	it( 'returns 400 if a lemma text is too long', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: 'x'.repeat( 1001 ) },
			lexical_category: lexicalCategoryId,
			language: languageId
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'value-too-long' );
		assert.deepStrictEqual( response.body.context, { path: '/lexeme/lemmas/en', limit: 1000 } );
	} );

	it( 'ignores statement ids provided in the request', async () => {
		const response = await newCreateLexemeRequestBuilder( newValidLexeme( {
			statements: {
				[ stringPropertyId ]: [ {
					id: 'L1$00000000-0000-0000-0000-000000000000',
					property: { id: stringPropertyId },
					value: { type: 'value', content: 'potato' }
				} ]
			}
		} ) ).makeRequest();

		expect( response ).to.have.status( 201 );
		assert.strictEqual(
			response.body.statements[ stringPropertyId ][ 0 ].id.split( '$' )[ 0 ],
			response.body.id
		);
	} );

	[
		{
			name: 'statements not an object',
			statements: () => [ 'potato' ],
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: '/lexeme/statements' } )
		},
		{
			name: 'statement group not a list',
			statements: () => ( {
				[ stringPropertyId ]: { property: { id: stringPropertyId } }
			} ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/lexeme/statements/${ stringPropertyId }` } )
		},
		{
			name: 'statement not an object',
			statements: () => ( { [ stringPropertyId ]: [ 'potato' ] } ),
			expectedCode: 'invalid-value',
			expectedContext: () => ( { path: `/lexeme/statements/${ stringPropertyId }/0` } )
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
			expectedContext: () => ( { path: `/lexeme/statements/${ stringPropertyId }/0/rank` } )
		},
		{
			name: 'statement field missing',
			statements: () => ( {
				[ stringPropertyId ]: [ { property: { id: stringPropertyId } } ]
			} ),
			expectedCode: 'missing-field',
			expectedContext: () => ( {
				path: `/lexeme/statements/${ stringPropertyId }/0`,
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
				path: '/lexeme/statements/P999999999/0/property/id'
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
				path: `/lexeme/statements/${ stringPropertyId }/0/property/id`,
				statement_group_property_id: stringPropertyId,
				statement_property_id: otherStringPropertyId
			} )
		}
	].forEach( ( { name, statements, expectedCode, expectedContext } ) => {
		it( `responds 400 - ${ name }`, async () => {
			const response = await newCreateLexemeRequestBuilder(
				newValidLexeme( { statements: statements() } )
			).makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, expectedCode );
			assert.deepStrictEqual( response.body.context, expectedContext() );
		} );
	} );

	it( 'can create a lexeme with edit metadata provided', async () => {
		const user = await action.robby();
		const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
		const editSummary = 'omg look i made an edit';

		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} )
			.withJsonBodyParam( 'tags', [ tag ] )
			.withJsonBodyParam( 'bot', true )
			.withJsonBodyParam( 'comment', editSummary )
			.withUser( user )
			.makeRequest();

		expect( response ).to.have.status( 201 );

		const editMetadata = await getLatestEditMetadata( response.body.id );
		assert.deepEqual( editMetadata.tags, [ tag ] );
		assert.property( editMetadata, 'bot' );
		assert.strictEqual(
			editMetadata.comment,
			`/* wbeditentity-create-lexeme:0| */ ${ editSummary }`
		);
		assert.strictEqual( editMetadata.user, user.username );
	} );

	it( 'returns 400 if an edit tag is invalid', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} )
			.withJsonBodyParam( 'tags', [ 'not-a-real-tag' ] )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-value' );
		assert.deepStrictEqual( response.body.context, { path: '/tags/0' } );
	} );

	it( 'returns 400 if the comment is too long', async () => {
		const response = await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId
		} )
			.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'value-too-long' );
		assert.deepStrictEqual( response.body.context, { path: '/comment', limit: 500 } );
	} );

} );
