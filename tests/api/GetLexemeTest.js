'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const {
	createLexeme,
	createRedirectForLexeme,
	getLatestEditMetadata
} = require( './helpers/entityHelper' );
const {
	newGetLexemeRequestBuilder,
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( './helpers/RequestBuilderFactory' );

describe( 'GET /entities/lexemes/{lexeme_id}', () => {
	let languageId;
	let lexicalCategoryId;
	let grammaticalFeature1Id;
	let grammaticalFeature2Id;
	let propertyId;
	let lexemeId;
	let testModified;
	let testRevisionId;

	before( async () => {
		languageId = ( await newCreateItemRequestBuilder(
			{ labels: { en: 'test-language' } }
		).makeRequest() ).body.id;

		lexicalCategoryId = ( await newCreateItemRequestBuilder(
			{ labels: { en: 'test-category' } }
		).makeRequest() ).body.id;

		grammaticalFeature1Id = ( await newCreateItemRequestBuilder(
			{ item: { labels: { en: 'test-grammatical-feature1' } } }
		).makeRequest() ).body.id;

		grammaticalFeature2Id = ( await newCreateItemRequestBuilder(
			{ item: { labels: { en: 'test-grammatical-feature2' } } }
		).makeRequest() ).body.id;

		propertyId = ( await newCreatePropertyRequestBuilder(
			{ data_type: 'string', labels: { en: `test-property-${ utils.uniq() }` } }
		).makeRequest() ).body.id;

		lexemeId = await createLexeme( {
			lemmas: {
				'en-ca': { language: 'en-ca', value: 'colour' },
				'en-us': { language: 'en-us', value: 'color' }
			},
			lexicalCategory: lexicalCategoryId,
			language: languageId,
			claims: [ {
				mainsnak: {
					snaktype: 'value',
					property: propertyId,
					datavalue: { value: 'potato', type: 'string' }
				},
				type: 'statement'
			} ],
			forms: [ {
				add: '',
				representations: {
					'en-gb': { language: 'en-gb', value: 'colourise' },
					'en-us': { language: 'en-us', value: 'colorize' }
				},
				grammaticalFeatures: [ grammaticalFeature1Id, grammaticalFeature2Id ],
				claims: [ {
					mainsnak: { snaktype: 'novalue', property: propertyId },
					type: 'statement'
				} ]
			} ],
			senses: [ {
				add: '',
				glosses: { en: { language: 'en', value: 'a colour' } },
				claims: [ {
					mainsnak: { snaktype: 'novalue', property: propertyId },
					type: 'statement'
				} ]
			} ]
		} );

		const testLexemeCreationMetadata = await getLatestEditMetadata( lexemeId );
		testModified = testLexemeCreationMetadata.timestamp;
		testRevisionId = testLexemeCreationMetadata.revid;

	} );

	it( 'returns the lexeme with all its data', async () => {
		const response = await newGetLexemeRequestBuilder( lexemeId )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body.id, lexemeId );
		assert.deepStrictEqual( response.body.lemmas, { 'en-ca': 'colour', 'en-us': 'color' } );
		assert.deepStrictEqual( response.body.lexical_category, lexicalCategoryId );
		assert.deepStrictEqual( response.body.language, languageId );

		assert.deepStrictEqual( Object.keys( response.body.statements ), [ propertyId ] );
		const [ statement ] = response.body.statements[ propertyId ];
		assert.strictEqual( statement.property.id, propertyId );
		assert.strictEqual( statement.property.data_type, 'string' );
		assert.strictEqual( statement.value.type, 'value' );
		assert.strictEqual( statement.value.content, 'potato' );
		assert.strictEqual( statement.rank, 'normal' );

		assert.strictEqual( response.body.forms.length, 1 );
		const form = response.body.forms[ 0 ];
		assert.strictEqual( form.id, `${ lexemeId }-F1` );
		assert.deepStrictEqual( form.representations, { 'en-gb': 'colourise', 'en-us': 'colorize' } );
		assert.deepStrictEqual(
			[ ...form.grammatical_features ].sort(),
			[ grammaticalFeature1Id, grammaticalFeature2Id ].sort()
		);
		assert.deepStrictEqual( Object.keys( form.statements ), [ propertyId ] );
		assert.strictEqual( form.statements[ propertyId ][ 0 ].property.id, propertyId );
		assert.strictEqual( form.statements[ propertyId ][ 0 ].value.type, 'novalue' );

		assert.strictEqual( response.body.senses.length, 1 );
		const sense = response.body.senses[ 0 ];
		assert.strictEqual( sense.id, `${ lexemeId }-S1` );
		assert.deepStrictEqual( sense.glosses, { en: 'a colour' } );
		assert.deepStrictEqual( Object.keys( sense.statements ), [ propertyId ] );
		assert.strictEqual( sense.statements[ propertyId ].length, 1 );
		assert.strictEqual( sense.statements[ propertyId ][ 0 ].property.id, propertyId );
		assert.strictEqual( sense.statements[ propertyId ][ 0 ].value.type, 'novalue' );

		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, `"${ testRevisionId }"` );
	} );

	it( 'responds with an X-Authenticated-User header for a logged in user', async () => {
		const user = await action.alice();
		const response = await newGetLexemeRequestBuilder( lexemeId )
			.withUser( user )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.header( response, 'X-Authenticated-User', user.username );
	} );

	it( 'responds with a 400 error if the User-Agent header is empty', async () => {
		const response = await newGetLexemeRequestBuilder( lexemeId )
			.withHeader( 'user-agent', '' )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, 'missing-user-agent' );
		assert.include( response.body.message, 'User-Agent' );
	} );

	it( 'responds with a 400 error if the lexeme id is invalid', async () => {
		const response = await newGetLexemeRequestBuilder( 'X123' )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.header( response, 'Content-Type', 'application/json' );
		assert.strictEqual( response.body.code, 'invalid-path-parameter' );
		assert.strictEqual( response.body.message, "Invalid path parameter: 'lexeme_id'" );
		assert.deepStrictEqual( response.body.context, { parameter: 'lexeme_id' } );
	} );

	it( 'responds with a 404 error if lexeme not found', async () => {
		const response = await newGetLexemeRequestBuilder( 'L999999' )
			.makeRequest();

		expect( response ).to.have.status( 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.header( response, 'Content-Type', 'application/json' );
		assert.strictEqual( response.body.code, 'resource-not-found' );
		assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		assert.deepStrictEqual( response.body.context, { resource_type: 'lexeme' } );
	} );

	describe( 'redirects', () => {
		let redirectSourceId;

		before( async () => {
			redirectSourceId = await createRedirectForLexeme(
				await createLexeme( {
					lemmas: { 'en-gb': { language: 'en-gb', value: 'colour' } },
					language: languageId,
					lexicalCategory: lexicalCategoryId
				} ),
				lexemeId
			);
		} );

		it( 'responds with a 308 including the redirect target location', async () => {
			const response = await newGetLexemeRequestBuilder( redirectSourceId )
				.makeRequest();

			expect( response ).to.have.status( 308 );
			assert.isTrue(
				new URL( response.header.location ).pathname.endsWith( `/entities/lexemes/${ lexemeId }` )
			);
		} );
	} );
} );
