'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const {
	getItemId,
	getStringPropertyId,
	newStatementWithRandomStringValue
} = require( './helpers/entityHelper' );
const {
	newAddLexemeStatementRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );

// This test is focused on the endpoints for creating a lexeme and adding a statement only.
// Testing other cases has been deemed too complex for now and can be revisited in the future
// if it's determined that the extra effort is worthwhile.
describe( 'resource too large', () => {
	let lexicalCategoryId;
	let languageId;
	const maxSizeInKb = 1;

	before( async () => {
		lexicalCategoryId = await getItemId();
		languageId = await getItemId();
	} );

	it( 'responds 400 - lexeme is too large', async () => {
		const statements = [];
		const propertyId = await getStringPropertyId();
		for ( let i = 0; i < 5; i++ ) {
			statements.push( newStatementWithRandomStringValue( propertyId ) );
		}

		const lexeme = {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId,
			statements: { [ propertyId ]: statements }
		};

		const response = await newCreateLexemeRequestBuilder( lexeme )
			.withConfigOverride( 'wgWBRepoSettings', { maxSerializedEntitySize: maxSizeInKb } )
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'resource-too-large' );
		assert.strictEqual(
			response.body.message,
			`Edit resulted in a resource that exceeds the size limit of ${ maxSizeInKb.toString() } kB`
		);
		assert.deepStrictEqual( response.body.context, { limit: maxSizeInKb } );
	} );

	it( 'responds 400 - lexeme is too large when adding a statement', async () => {
		const statements = [];
		const propertyId = await getStringPropertyId();
		for ( let i = 0; i < 3; i++ ) {
			statements.push( newStatementWithRandomStringValue( propertyId ) );
		}
		const lexeme = {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: lexicalCategoryId,
			language: languageId,
			statements: { [ propertyId ]: statements }
		};

		const lexemeId = ( await newCreateLexemeRequestBuilder( lexeme )
			.makeRequest() ).body.id;

		const response = await newAddLexemeStatementRequestBuilder(
			lexemeId,
			newStatementWithRandomStringValue( propertyId )
		)
			.withConfigOverride(
				'wgWBRepoSettings',
				{ maxSerializedEntitySize: maxSizeInKb }
			)
			.makeRequest();

		expect( response ).to.have.status( 400 );
		assert.strictEqual( response.body.code, 'resource-too-large' );
		assert.strictEqual(
			response.body.message,
			`Edit resulted in a resource that exceeds the size limit of ${ maxSizeInKb.toString() } kB`
		);
		assert.deepStrictEqual( response.body.context, { limit: maxSizeInKb } );
	} );
} );
