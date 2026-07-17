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
	let testModified;
	let testRevisionId;

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
				lemmas: {
					'en-ca': { language: 'en-ca', value: 'colour' },
					'en-us': { language: 'en-us', value: 'color' }
				},
				language: languageId,
				lexicalCategory: lexicalCategoryId
			} ),
			token: await anon.token()
		}, 'POST' );
		lexemeId = id;

		const testLexemeCreationMetadata = await getLatestEditMetadata( lexemeId );
		testModified = testLexemeCreationMetadata.timestamp;
		testRevisionId = testLexemeCreationMetadata.revid;

	} );

	it( 'returns the lexeme with the requested ID and lemmas', async () => {
		const response = await client.get( `/entities/lexemes/${ lexemeId }` );

		assert.strictEqual( response.status, 200, response.text );
		assert.deepStrictEqual( response.body, { id: lexemeId, lemmas: { 'en-ca': 'colour', 'en-us': 'color' } } );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, `"${ testRevisionId }"` );
	} );

	async function getLatestEditMetadata( id ) {
		const editMetadata = ( await action.getAnon().action( 'query', {
			list: 'recentchanges',
			rctitle: `Lexeme:${ id }`,
			rclimit: 1,
			rcprop: 'tags|flags|comment|ids|timestamp|user'
		} ) ).query.recentchanges[ 0 ];

		return {
			...editMetadata,
			timestamp: new Date( editMetadata.timestamp ).toUTCString()
		};
	}

} );
