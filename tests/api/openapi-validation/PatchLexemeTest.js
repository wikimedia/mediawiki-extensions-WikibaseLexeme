'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { newPatchLexemeRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const {
	createLexeme,
	createRedirectForLexeme,
	getItemId
} = require( '../helpers/entityHelper' );
const fragment = require( '../../../src/MediaWiki/RestApi/specs/openapi.fragment.dereferenced.json' );

const UNREACHABLE_STATUSES = [ '500' ];

describe( newPatchLexemeRequestBuilder().getRouteDescription(), () => {

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
		testData.blockedUser = await action.blockedUser();
	} );

	const casesByStatus = {
		200: [ [
			'patch a lexeme',
			() => newPatchLexemeRequestBuilder( testData.lexemeId, [
				{ op: 'replace', path: '/lemmas/en', value: `test-lemma-${ utils.uniq() }` }
			] )
		] ],
		400: [ [
			'invalid patch',
			() => newPatchLexemeRequestBuilder( testData.lexemeId, [ { invalid: 'patch' } ] )
		] ],
		403: [ [
			'blocked user',
			() => newPatchLexemeRequestBuilder( testData.lexemeId, [
				{ op: 'replace', path: '/lemmas/en', value: `test-lemma-${ utils.uniq() }` }
			] ).withUser( testData.blockedUser )
		] ],
		404: [ [
			'a Lexeme that does not exist',
			() => newPatchLexemeRequestBuilder( 'L999999', [
				{ op: 'replace', path: '/lemmas/en', value: `test-lemma-${ utils.uniq() }` }
			] )
		] ],
		409: [ [
			'a redirected Lexeme',
			() => newPatchLexemeRequestBuilder( testData.redirectSourceId, [
				{ op: 'replace', path: '/lemmas/en', value: `test-lemma-${ utils.uniq() }` }
			] )
		] ],
		412: [ [
			'a stale If-Unmodified-Since',
			() => newPatchLexemeRequestBuilder( testData.lexemeId, [
				{ op: 'replace', path: '/lemmas/en', value: `test-lemma-${ utils.uniq() }` }
			] ).withHeader(
				'If-Unmodified-Since',
				new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString()
			)
		] ],
		422: [ [
			'patch results in an invalid lexeme',
			() => newPatchLexemeRequestBuilder( testData.lexemeId, [
				{ op: 'remove', path: '/language' }
			] )
		] ],
		429: [ [
			'rate limit reached',
			() => newPatchLexemeRequestBuilder( testData.lexemeId, [
				{ op: 'replace', path: '/lemmas/en', value: `test-lemma-${ utils.uniq() }` }
			] ).withConfigOverride( 'wgRateLimits', { edit: { anon: [ 0, 60 ] } } )
		] ]
	};

	it( 'covers every documented response status', () => {
		const builder = newPatchLexemeRequestBuilder();
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
