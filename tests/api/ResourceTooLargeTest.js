'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( './helpers/chaiHelper' );
const { newStatementWithRandomStringValue } = require( './helpers/entityHelper' );
const {
	newCreateLexemeRequestBuilder,
	newCreatePropertyRequestBuilder,
	newCreateItemRequestBuilder
} = require( './helpers/RequestBuilderFactory' );

describe( 'resource too large', () => {
	let lexicalCategoryId;
	let languageId;
	let propertyId;
	const statements = [];
	const maxSizeInKb = 1;

	before( async () => {
		lexicalCategoryId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		languageId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		propertyId = ( await newCreatePropertyRequestBuilder(
			{ data_type: 'string', labels: { en: `string-property-${ utils.uniq() }` } }
		).makeRequest() ).body.id;
		for ( let i = 0; i < 5; i++ ) {
			statements.push( newStatementWithRandomStringValue( propertyId ) );
		}
	} );

	it( 'lexeme is too large', async () => {
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

} );
