'use strict';

const { action, utils } = require( 'api-testing' );
const {
	newCreateItemRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( './RequestBuilderFactory' );

let testItemId;
let otherTestItemId;
let testLexemeId;
let stringPropertyId;
let otherStringPropertyId;

/**
 * Creates a reusable item on the first call and returns it on subsequent calls.
 * Use this only when the existing item data does not matter.
 *
 * @return {Promise<string>} - the id of the item
 */
async function getItemId() {
	testItemId = testItemId || ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;

	return testItemId;
}

/**
 * Like getItemId(), but returns a different item. Use this when a test needs two distinct items.
 *
 * @return {Promise<string>} - the id of the item
 */
async function getOtherItemId() {
	otherTestItemId = otherTestItemId || ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;

	return otherTestItemId;
}

/**
 * Creates a reusable lexeme on the first call and returns it on subsequent calls.
 * Use this only when the existing lexeme data does not matter.
 *
 * @return {Promise<string>} - the id of the lexeme
 */
async function getLexemeId() {
	testLexemeId = testLexemeId || ( await createLexeme(
		{
			lemmas: { en: { language: 'en', value: `test-lemma-${ utils.uniq() }` } },
			lexicalCategory: await getItemId(),
			language: await getItemId()
		}
	) );

	return testLexemeId;
}

async function createLexeme( lexeme ) {
	const anon = await action.getAnon();
	const { entity: { id } } = await anon.action( 'wbeditentity', {
		new: 'lexeme',
		data: JSON.stringify( lexeme ),
		token: await anon.token()
	}, 'POST' );

	return id;
}

async function createRedirectForLexeme( sourceId, targetId ) {
	const anon = await action.getAnon();
	await anon.action( 'wblmergelexemes', {
		source: sourceId,
		target: targetId,
		token: await anon.token()
	}, 'POST' );

	return sourceId;
}

async function getLatestEditMetadata( lexemeId ) {
	const editMetadata = ( await action.getAnon().action( 'query', {
		list: 'recentchanges',
		rctitle: `Lexeme:${ lexemeId }`,
		rclimit: 1,
		rcprop: 'tags|flags|comment|ids|timestamp|user'
	} ) ).query.recentchanges[ 0 ];

	return {
		...editMetadata,
		timestamp: new Date( editMetadata.timestamp ).toUTCString()
	};
}

/**
 * @param {string} propertyId
 * @return {{property: {id: string}, value: {type: string, content: string}}}
 */
function newStatementWithRandomStringValue( propertyId ) {
	return {
		property: {
			id: propertyId
		},
		value: {
			type: 'value',
			content: 'random-string-value-' + utils.uniq()
		}
	};
}

/**
 * Creates a reusable property on the first call and returns it on subsequent calls.
 * Use this only when the existing property data does not matter.
 *
 * @return {Promise<string>} - the id of the property
 */
async function getStringPropertyId() {
	stringPropertyId = stringPropertyId || ( await createUniqueStringProperty() ).body.id;

	return stringPropertyId;
}

/**
 * Like getStringPropertyId(), but returns a different property. Use this when a test needs two distinct properties.
 *
 * @return {Promise<string>} - the id of the property
 */
async function getOtherStringPropertyId() {
	otherStringPropertyId = otherStringPropertyId || ( await createUniqueStringProperty() ).body.id;

	return otherStringPropertyId;
}

async function createUniqueStringProperty() {
	return await newCreatePropertyRequestBuilder( {
		data_type: 'string',
		labels: { en: `string-property-${ utils.uniq() }` }
	} ).makeRequest();
}

async function changeLexemeProtectionStatus( lexemeId, allowedUserGroup ) {
	const admin = await action.root();
	await admin.action( 'protect', {
		title: `Lexeme:${ lexemeId }`,
		token: await admin.token(),
		protections: `edit=${ allowedUserGroup }`,
		expiry: 'infinite'
	}, 'POST' );
}

module.exports = {
	getItemId,
	getOtherItemId,
	getLexemeId,
	createLexeme,
	createRedirectForLexeme,
	getLatestEditMetadata,
	newStatementWithRandomStringValue,
	getStringPropertyId,
	getOtherStringPropertyId,
	changeLexemeProtectionStatus
};
