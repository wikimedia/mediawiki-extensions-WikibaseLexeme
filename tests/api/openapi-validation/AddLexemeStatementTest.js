'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createLexeme,
	createRedirectForLexeme,
	getItemId,
	getStringPropertyId
} = require( '../helpers/entityHelper' );
const {
	newCreateLexemeRequestBuilder,
	newAddLexemeStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

const fragment = require( '../../../src/MediaWiki/RestApi/specs/openapi.fragment.dereferenced.json' );

const UNREACHABLE_STATUSES = [ '500' ];

describe( newCreateLexemeRequestBuilder().getRouteDescription(), () => {

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
			'creating a lexeme statement',
			() => newAddLexemeStatementRequestBuilder( testData.lexemeId, {
				property: { id: testData.stringPropertyId },
				value: { type: 'value', content: 'potato' }
			} )
		] ],
		400: [ [
			'missing property',
			() => newAddLexemeStatementRequestBuilder( testData.lexemeId, {
				value: { type: 'value', content: 'potato' }
			} )
		] ],
		403: [ [
			'blocked user',
			() => newAddLexemeStatementRequestBuilder( testData.lexemeId, {
				property: { id: testData.stringPropertyId },
				value: { type: 'value', content: 'potato' }
			} ).withUser( testData.blockedUser )
		] ],
		404: [ [
			'a Lexeme that does not exist',
			() => newAddLexemeStatementRequestBuilder( 'L999999', {
				property: { id: testData.stringPropertyId },
				value: { type: 'value', content: 'potato' }
			} )
		] ],
		409: [ [
			'a redirected Lexeme',
			() => newAddLexemeStatementRequestBuilder( testData.redirectSourceId, {
				property: { id: testData.stringPropertyId },
				value: { type: 'value', content: 'potato' }
			} )
		] ],
		412: [ [
			'a stale If-Unmodified-Since',
			() => newAddLexemeStatementRequestBuilder( testData.lexemeId, {
				property: { id: testData.stringPropertyId },
				value: { type: 'value', content: 'potato' }
			} ).withHeader(
				'If-Unmodified-Since',
				new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString()
			)
		] ],
		429: [ [
			'rate limit reached',
			() => newAddLexemeStatementRequestBuilder( testData.lexemeId, {
				property: { id: testData.stringPropertyId },
				value: { type: 'value', content: 'potato' }
			} ).withConfigOverride( 'wgRateLimits', { edit: { anon: [ 0, 60 ] } } )
		] ]
	};

	it( 'covers every documented response status', () => {
		const builder = newAddLexemeStatementRequestBuilder();
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
