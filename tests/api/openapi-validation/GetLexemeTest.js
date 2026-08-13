'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createLexeme,
	createRedirectForLexeme,
	getLatestEditMetadata
} = require( '../helpers/entityHelper' );
const {
	newGetLexemeRequestBuilder,
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const fragment = require( '../../../src/MediaWiki/RestApi/specs/openapi.fragment.dereferenced.json' );

const UNREACHABLE_STATUSES = [ '500' ];

async function createItemId( label ) {
	return ( await newCreateItemRequestBuilder(
		{ labels: { en: `${ label }-${ utils.uniq() }` } }
	).makeRequest() ).body.id;
}

async function createLexemeWithAllFields( language, lexicalCategory ) {
	const grammaticalFeature = await createItemId( 'grammatical-feature' );
	const propertyId = ( await newCreatePropertyRequestBuilder( {
		// eslint-disable-next-line camelcase
		data_type: 'string',
		labels: { en: `spec-test-property-${ utils.uniq() }` }
	} ).makeRequest() ).body.id;

	const statementWithQualifiersAndReferences = {
		mainsnak: {
			snaktype: 'value',
			property: propertyId,
			datavalue: { value: 'statement value', type: 'string' }
		},
		type: 'statement',
		rank: 'preferred',
		qualifiers: { [ propertyId ]: [ {
			snaktype: 'value',
			property: propertyId,
			datavalue: { value: 'qualifier value', type: 'string' }
		} ] },
		references: [ { snaks: { [ propertyId ]: [ {
			snaktype: 'value',
			property: propertyId,
			datavalue: { value: 'reference value', type: 'string' }
		} ] } } ]
	};
	const someValueStatement = {
		mainsnak: { snaktype: 'somevalue', property: propertyId },
		type: 'statement',
		rank: 'deprecated'
	};
	const noValueStatement = {
		mainsnak: { snaktype: 'novalue', property: propertyId },
		type: 'statement'
	};

	return createLexeme( {
		lemmas: {
			'en-ca': { language: 'en-ca', value: 'colour' },
			'en-us': { language: 'en-us', value: 'color' }
		},
		language,
		lexicalCategory,
		claims: [ statementWithQualifiersAndReferences, someValueStatement ],
		forms: [ {
			add: '',
			representations: { 'en-gb': { language: 'en-gb', value: 'colourise' } },
			grammaticalFeatures: [ grammaticalFeature ],
			claims: [ noValueStatement ]
		} ],
		senses: [ {
			add: '',
			glosses: { en: { language: 'en', value: 'a colour' } },
			claims: [ noValueStatement ]
		} ]
	} );
}

describe( newGetLexemeRequestBuilder().getRouteDescription(), () => {

	const testData = {};

	before( async () => {
		const language = await createItemId( 'language' );
		const lexicalCategory = await createItemId( 'lexical-category' );

		testData.lexemeId = await createLexemeWithAllFields( language, lexicalCategory );

		testData.minimalLexemeId = await createLexeme( {
			lemmas: { en: { language: 'en', value: `minimal-${ utils.uniq() }` } },
			language,
			lexicalCategory
		} );

		testData.redirectSourceId = await createRedirectForLexeme(
			await createLexeme( {
				lemmas: { 'en-gb': { language: 'en-gb', value: `colour-${ utils.uniq() }` } },
				language,
				lexicalCategory
			} ),
			testData.lexemeId
		);

		testData.latestRevisionId = ( await getLatestEditMetadata( testData.lexemeId ) ).revid;

		testData.user = await action.alice();
	} );

	const casesByStatus = {
		200: [
			[ 'a Lexeme with all fields', () => newGetLexemeRequestBuilder( testData.lexemeId ) ],
			[
				'a Lexeme with only required fields',
				() => newGetLexemeRequestBuilder( testData.minimalLexemeId )
			],
			[
				'an authenticated request',
				() => newGetLexemeRequestBuilder( testData.lexemeId ).withUser( testData.user )
			]
		],
		304: [
			[
				'a matching If-None-Match',
				() => newGetLexemeRequestBuilder( testData.lexemeId )
					.withHeader( 'If-None-Match', makeEtag( testData.latestRevisionId ) )
			]
		],
		308: [
			[ 'a redirected Lexeme', () => newGetLexemeRequestBuilder( testData.redirectSourceId ) ]
		],
		400: [
			[ 'an invalid Lexeme ID', () => newGetLexemeRequestBuilder( 'X123' ) ],
			[
				'an empty User-Agent',
				() => newGetLexemeRequestBuilder( testData.lexemeId ).withHeader( 'user-agent', '' )
			]
		],
		404: [
			[ 'a Lexeme that does not exist', () => newGetLexemeRequestBuilder( 'L999999' ) ]
		],
		412: [
			[
				'a stale If-Unmodified-Since',
				() => newGetLexemeRequestBuilder( testData.lexemeId ).withHeader(
					'If-Unmodified-Since',
					new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString()
				)
			]
		]
	};

	it( 'covers every documented response status', () => {
		const builder = newGetLexemeRequestBuilder();
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
