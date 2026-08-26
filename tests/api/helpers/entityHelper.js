'use strict';

const { action, utils } = require( 'api-testing' );
const { newCreateItemRequestBuilder } = require( './RequestBuilderFactory' );

let testItemId;
let testLexemeId;

/**
 * Creates a reusable item on the first call and returns it on subsequent calls.
 * Use this only when the existing item data does not matter.
 */

async function getItemId() {

	const response = ( await newCreateItemRequestBuilder( {} ).makeRequest() );
	testItemId = testItemId || response.body.id;
	return testItemId;
}

/**
 * Creates a reusable lexeme on the first call and returns it on subsequent calls.
 * Use this only when the existing lexeme data does not matter.
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

module.exports = {
	getLexemeId,
	createLexeme,
	createRedirectForLexeme,
	getLatestEditMetadata,
	newStatementWithRandomStringValue
};
