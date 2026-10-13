'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createLexeme,
	createRedirectForLexeme,
	getItemId,
	getStringPropertyId
} = require( '../helpers/entityHelper' );
const { newAddLexemeSenseRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

const fragment = require( '../../../src/MediaWiki/RestApi/specs/openapi.fragment.dereferenced.json' );

const UNREACHABLE_STATUSES = [ '500' ];

describe( newAddLexemeSenseRequestBuilder().getRouteDescription(), () => {

	const testData = {};

	before( async () => {
		const language = await getItemId();
		const lexicalCategory = await getItemId();
		testData.lexemeId = await createLexeme( {
			lemmas: { en: { language: 'en', value: `test-lexeme-${ utils.uniq() }` } },
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
		testData.stringPropertyId = await getStringPropertyId();
		testData.blockedUser = await action.blockedUser();
	} );

	const casesByStatus = {
		201: [ [
			'creating a lexeme sense with all fields',
			() => newAddLexemeSenseRequestBuilder( testData.lexemeId, {
				glosses: { en: 'a test sense' },
				statements: {
					[ testData.stringPropertyId ]: [
						{
							property: { id: testData.stringPropertyId },
							value: { type: 'value', content: 'statement value' },
							rank: 'preferred',
							qualifiers: [ {
								property: { id: testData.stringPropertyId },
								value: { type: 'value', content: 'qualifier value' }
							} ],
							references: [ {
								parts: [ {
									property: { id: testData.stringPropertyId },
									value: { type: 'value', content: 'reference value' }
								} ]
							} ]
						}
					]
				}
			} )
		] ],
		400: [ [
			'missing glosses',
			() => newAddLexemeSenseRequestBuilder( testData.lexemeId, { } )
		] ],
		403: [ [
			'blocked user',
			() => newAddLexemeSenseRequestBuilder( testData.lexemeId, {
				glosses: { en: 'a test sense' }
			} ).withUser( testData.blockedUser )
		] ],
		404: [ [
			'a Lexeme that does not exist',
			() => newAddLexemeSenseRequestBuilder( 'L999999', {
				glosses: { en: 'a test sense' }
			} )
		] ],
		409: [ [
			'a redirected Lexeme',
			() => newAddLexemeSenseRequestBuilder( testData.redirectSourceId, {
				glosses: { en: 'a test sense' }
			} )
		] ],
		412: [ [
			'a stale If-Unmodified-Since',
			() => newAddLexemeSenseRequestBuilder( testData.lexemeId, {
				glosses: { en: 'a test sense' }
			} ).withHeader(
				'If-Unmodified-Since',
				new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString()
			)
		] ],
		429: [ [
			'rate limit reached',
			() => newAddLexemeSenseRequestBuilder( testData.lexemeId, {
				glosses: { en: 'a test sense' }
			} ).withConfigOverride( 'wgRateLimits', { edit: { anon: [ 0, 60 ] } } )
		] ]
	};

	it( 'covers every documented response status', () => {
		const builder = newAddLexemeSenseRequestBuilder();
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
