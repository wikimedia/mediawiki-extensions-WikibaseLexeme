'use strict';

const { assert, action, utils } = require( 'api-testing' );
const {
	newCreateLexemeRequestBuilder,
	newGetLexemeRequestBuilder,
	newPatchLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { expect } = require( './helpers/chaiHelper' );
const testValidatesPatch = require( '../../../Wikibase/repo/domains/crud/tests/mocha/helpers/testValidatesPatch' );
const {
	getItemId,
	getLatestEditMetadata,
	createRedirectForLexeme
} = require( './helpers/entityHelper' );
const { assertValidError } = require( './helpers/responseValidator' );

describe( 'PATCH /entities/lexemes/{lexeme_id}', () => {
	let itemId;
	let lexemeId;
	let originalEtag;
	let originalLastModified;

	before( async () => {
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

	it( 'patches the lemmas', async () => {
		const newLemma = `test-lemma-${ utils.uniq() }`;
		const otherNewLemma = `test-lemma-${ utils.uniq() }`;

		const response = await newPatchLexemeRequestBuilder( lexemeId, [
			{ op: 'replace', path: '/lemmas/en', value: newLemma },
			{ op: 'add', path: '/lemmas/en-gb', value: otherNewLemma }
		] ).makeRequest();

		expect( response ).to.have.status( 200 );
		assert.match( response.header.etag, /^"\d+"$/ );
		assert.notStrictEqual( response.header.etag, originalEtag );
		assert.isAbove(
			new Date( response.header[ 'last-modified' ] ),
			originalLastModified
		);
		const expectedLemmas = { en: newLemma, 'en-gb': otherNewLemma };
		assert.deepStrictEqual( response.body.lemmas, expectedLemmas );

		const getResponse = await newGetLexemeRequestBuilder( lexemeId ).makeRequest();
		assert.deepStrictEqual( getResponse.body.lemmas, expectedLemmas );
	} );

	it( 'patches a lexeme with edit metadata provided', async () => {
		const user = await action.robby();
		const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
		const editSummary = 'omg look i patched a lexeme';
		const newLemma = `test-lemma-${ utils.uniq() }`;
		const otherNewLemma = `test-lemma-${ utils.uniq() }`;

		const response = await newPatchLexemeRequestBuilder( lexemeId, [
			{ op: 'replace', path: '/lemmas/en', value: newLemma },
			{ op: 'add', path: '/lemmas/en-gb', value: otherNewLemma }
		] ).withJsonBodyParam( 'tags', [ tag ] )
			.withJsonBodyParam( 'bot', true )
			.withJsonBodyParam( 'comment', editSummary )
			.withUser( user )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		const editMetadata = await getLatestEditMetadata( lexemeId );
		assert.deepEqual( editMetadata.tags, [ tag ] );
		assert.property( editMetadata, 'bot' );
		assert.strictEqual(
			editMetadata.comment,
			`/* wbeditentity-update:0| */ ${ editSummary }`
		);
		assert.strictEqual( editMetadata.user, user.username );
	} );

	it( 'responds 400 if the lexeme id is invalid', async () => {
		const response = await newPatchLexemeRequestBuilder( 'not-a-lexeme-id', [
			{ op: 'add', path: '/lemmas/en-gb', value: `test-lemma-${ utils.uniq() }` }
		] ).makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'invalid-path-parameter' );
		assert.deepStrictEqual( response.body.context, { parameter: 'lexeme_id' } );
	} );

	it( 'returns 404 if the lexeme does not exist', async () => {
		const response = await newPatchLexemeRequestBuilder( 'L999999', [] ).makeRequest();

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

		const response = await newPatchLexemeRequestBuilder( sourceLexemeId, [] )
			.makeRequest();

		expect( response ).to.have.status( 409 );

		assert.deepStrictEqual( response.body, {
			code: 'resource-redirected',
			message: `Resource ${ sourceLexemeId } has been redirected to ${ lexemeId }.`,
			context: {
				redirect_target: lexemeId
			}
		} );
	} );

	testValidatesPatch( ( patch ) => newPatchLexemeRequestBuilder( lexemeId, patch ) );

	describe( '409 error response', () => {
		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchLexemeRequestBuilder( lexemeId, [ operation ] ).makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { path: '/patch/0/path' } );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/lemmas/en' };

			const response = await newPatchLexemeRequestBuilder( lexemeId, [ operation ] ).makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { path: '/patch/0/from' } );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( 'patch test condition failed', async () => {
			const currentLemma = ( await newGetLexemeRequestBuilder( lexemeId ).makeRequest() ).body.lemmas.en;
			const operation = { op: 'test', path: '/lemmas/en', value: `not-${ currentLemma }` };

			const response = await newPatchLexemeRequestBuilder( lexemeId, [ operation ] ).makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { path: '/patch/0', actual_value: currentLemma } );
			assert.strictEqual( response.body.message, 'Test operation in the provided patch failed' );
		} );
	} );

	describe( '422 error response', () => {
		it( 'returns patch-result-missing-field when the patch removes language', async () => {
			const response = await newPatchLexemeRequestBuilder( lexemeId, [
				{ op: 'remove', path: '/language' }
			] ).makeRequest();

			assertValidError( response, 422, 'patch-result-missing-field', {
				path: '',
				field: 'language'
			} );
		} );
	} );

} );
