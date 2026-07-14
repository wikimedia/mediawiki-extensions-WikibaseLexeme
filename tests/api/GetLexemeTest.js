/* eslint-env mocha */
'use strict';

const { assert, action, REST } = require( 'api-testing' );

const client = new REST( 'rest.php/wikibase/v0' );
// TODO create something like Wikibase.ci.php to avoid loading routes this way
client.req.set(
	'X-Config-Override',
	JSON.stringify( {
		wgRestAPIAdditionalRouteFiles: [
			'extensions/WikibaseLexeme/src/MediaWiki/RestApi/routes.dev.json'
		]
	} )
);

const crudClient = new REST( '/rest.php/wikibase/v1' );
crudClient.req.set( 'User-Agent', 'api-tests' );

describe( 'GET /entities/lexemes/{lexeme_id}', () => {
	let lexemeId;

	before( async () => {
		const { body: { id: languageId } } = await crudClient.post(
			'/entities/items',
			{ item: { labels: { en: 'test-language' } } }
		);

		const { body: { id: lexicalCategoryId } } = await crudClient.post(
			'/entities/items',
			{ item: { labels: { en: 'test-lexical-category' } } }
		);

		const anon = await action.getAnon();
		const { entity: { id } } = await anon.action( 'wbeditentity', {
			new: 'lexeme',
			data: JSON.stringify( {
				lemmas: { en: { language: 'en', value: 'ice' } },
				language: languageId,
				lexicalCategory: lexicalCategoryId
			} ),
			token: await anon.token()
		}, 'POST' );
		lexemeId = id;
	} );

	it( 'returns the lexeme with the requested ID', async () => {
		const response = await client.get( `/entities/lexemes/${ lexemeId }` );

		assert.strictEqual( response.status, 200, response.text );
		assert.deepStrictEqual( response.body, { id: lexemeId } );
	} );
} );
