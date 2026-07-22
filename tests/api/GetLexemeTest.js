/* eslint-env mocha */
'use strict';

const { assert, action, utils, REST } = require( 'api-testing' );

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
	let languageId;
	let lexicalCategoryId;
	let propertyId;
	let lexemeId;
	let testModified;
	let testRevisionId;

	async function createLexeme( lexeme ) {
		const anon = await action.getAnon();
		const { entity: { id } } = await anon.action( 'wbeditentity', {
			new: 'lexeme',
			data: JSON.stringify( lexeme ),
			token: await anon.token()
		}, 'POST' );

		return id;
	}

	before( async () => {
		const languageResponse = await crudClient.post(
			'/entities/items',
			{ item: { labels: { en: 'test-language' } } }
		);
		languageId = languageResponse.body.id;

		const lexicalCategoryResponse = await crudClient.post(
			'/entities/items',
			{ item: { labels: { en: 'test-lexical-category' } } }
		);
		lexicalCategoryId = lexicalCategoryResponse.body.id;

		const propertyResponse = await crudClient.post(
			'/entities/properties',
			// eslint-disable-next-line camelcase
			{ property: { data_type: 'string', labels: { en: `test-property-${ utils.uniq() }` } } }
		);
		propertyId = propertyResponse.body.id;

		lexemeId = await createLexeme( {
			lemmas: {
				'en-ca': { language: 'en-ca', value: 'colour' },
				'en-us': { language: 'en-us', value: 'color' }
			},
			language: languageId,
			lexicalCategory: lexicalCategoryId,
			claims: [ {
				mainsnak: {
					snaktype: 'value',
					property: propertyId,
					datavalue: { value: 'potato', type: 'string' }
				},
				type: 'statement'
			} ],
			senses: [ {
				add: '',
				glosses: { en: { language: 'en', value: 'a colour' } }
			} ]
		} );

		const testLexemeCreationMetadata = await getLatestEditMetadata( lexemeId );
		testModified = testLexemeCreationMetadata.timestamp;
		testRevisionId = testLexemeCreationMetadata.revid;

	} );

	it( 'returns the lexeme with the requested ID, lemmas, statements and senses', async () => {
		const response = await client.get( `/entities/lexemes/${ lexemeId }` );

		assert.strictEqual( response.status, 200, response.text );
		assert.strictEqual( response.body.id, lexemeId );
		assert.deepStrictEqual( response.body.lemmas, { 'en-ca': 'colour', 'en-us': 'color' } );

		assert.deepStrictEqual( Object.keys( response.body.statements ), [ propertyId ] );
		const [ statement ] = response.body.statements[ propertyId ];
		assert.strictEqual( statement.property.id, propertyId );
		assert.strictEqual( statement.property.data_type, 'string' );
		assert.strictEqual( statement.value.type, 'value' );
		assert.strictEqual( statement.value.content, 'potato' );
		assert.strictEqual( statement.rank, 'normal' );

		assert.deepStrictEqual( response.body.senses, [
			{ id: `${ lexemeId }-S1`, glosses: { en: 'a colour' } }
		] );

		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, `"${ testRevisionId }"` );
	} );

	it( 'responds with a 404 error if lexeme not found', async () => {
		const response = await client.get( '/entities/lexemes/L999999' );

		assert.strictEqual( response.status, 404, response.text );
		assert.header( response, 'Content-Language', 'en' );
		assert.header( response, 'Content-Type', 'application/json' );
		assert.strictEqual( response.body.code, 'lexeme-not-found' );
		assert.strictEqual( response.body.message, 'The requested lexeme does not exist' );
	} );

	describe( 'redirects', () => {
		let redirectSourceId;

		before( async () => {
			redirectSourceId = await createLexeme( {
				lemmas: { 'en-gb': { language: 'en-gb', value: 'colour' } },
				language: languageId,
				lexicalCategory: lexicalCategoryId
			} );

			const anon = await action.getAnon();
			await anon.action( 'wblmergelexemes', {
				source: redirectSourceId,
				target: lexemeId,
				token: await anon.token()
			}, 'POST' );
		} );

		it( 'responds with a 308 including the redirect target location', async () => {
			const response = await client.get( `/entities/lexemes/${ redirectSourceId }` );

			assert.strictEqual( response.status, 308, response.text );
			assert.isTrue(
				new URL( response.header.location ).pathname.endsWith( `/entities/lexemes/${ lexemeId }` )
			);
		} );
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
