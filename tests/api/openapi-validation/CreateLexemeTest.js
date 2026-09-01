'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	newCreateLexemeRequestBuilder,
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const fragment = require( '../../../src/MediaWiki/RestApi/specs/openapi.fragment.dereferenced.json' );

const UNREACHABLE_STATUSES = [ '500' ];

async function createItem( label ) {
	return ( await newCreateItemRequestBuilder( {
		labels: { en: `${ label }-${ utils.uniq() }` }
	} ).makeRequest() ).body.id;
}

describe( newCreateLexemeRequestBuilder().getRouteDescription(), () => {

	const testData = {};

	before( async () => {
		testData.language = await createItem( 'language' );
		testData.lexicalCategory = await createItem( 'lexical-category' );
		testData.propertyId = ( await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `spec-test-property-${ utils.uniq() }` }
		} ).makeRequest() ).body.id;
		testData.blockedUser = await action.blockedUser();
	} );

	const casesByStatus = {
		201: [ [
			'creating a lexeme with all fields',
			() => newCreateLexemeRequestBuilder( {
				lemmas: {
					'en-ca': 'colour',
					'en-us': 'color'
				},
				language: testData.language,
				lexical_category: testData.lexicalCategory,
				statements: {
					[ testData.propertyId ]: [
						{
							property: { id: testData.propertyId },
							value: { type: 'value', content: 'statement value' },
							rank: 'preferred',
							qualifiers: [ {
								property: { id: testData.propertyId },
								value: { type: 'value', content: 'qualifier value' }
							} ],
							references: [ {
								parts: [ {
									property: { id: testData.propertyId },
									value: { type: 'value', content: 'reference value' }
								} ]
							} ]
						}
					]
				}
			} )
		] ],
		400: [ [
			'missing lemmas',
			() => newCreateLexemeRequestBuilder( {
				language: testData.language,
				lexical_category: testData.lexicalCategory
			} )
		] ],
		403: [ [
			'blocked user',
			() => newCreateLexemeRequestBuilder( {
				lemmas: { en: `minimal-valid-lexeme-${ utils.uniq() }` },
				language: testData.language,
				lexical_category: testData.lexicalCategory
			} ).withUser( testData.blockedUser )
		] ],
		429: [ [
			'rate limit reached',
			() => newCreateLexemeRequestBuilder( {
				lemmas: { en: `minimal-valid-lexeme-${ utils.uniq() }` },
				language: testData.language,
				lexical_category: testData.lexicalCategory
			} ).withConfigOverride( 'wgRateLimits', { edit: { anon: [ 0, 60 ] } } )
		] ]
	};

	it( 'covers every documented response status', () => {
		const builder = newCreateLexemeRequestBuilder();
		const documented = Object.keys(
			fragment.paths[ builder.route ][ builder.method.toLowerCase() ].responses
		).filter( ( status ) => !UNREACHABLE_STATUSES.includes( status ) );

		const covered = Object.keys( casesByStatus ).filter( ( status ) => casesByStatus[ status ].length );

		assert.deepStrictEqual( covered.sort(), documented.sort() );
	} );

	Object.entries( casesByStatus ).forEach( ( [ status, cases ] ) => {
		cases.forEach( ( [ description, newRequestBuilder ] ) => {
			it( `${ status } response is valid for ${ description }`, async () => {
				const response = await newRequestBuilder().makeRequest();

				expect( response ).to.have.status( Number( status ) );
				expect( response ).to.satisfyApiSchema();
			} );
		} );
	} );
} );
