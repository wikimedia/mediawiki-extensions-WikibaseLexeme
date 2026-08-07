/* eslint-env node, mocha */
'use strict';

const { strict: assert } = require( 'assert' );
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

const EXTENSION_ROOT = path.resolve( __dirname, '../..' );
const FRAGMENT_ENTRY = path.join( EXTENSION_ROOT, 'src/MediaWiki/RestApi/specs/index.fragment.js' );
const GENERATED_FRAGMENT = path.join(
	EXTENSION_ROOT,
	'src/MediaWiki/RestApi/specs/openapi.fragment.generated.json'
);
const COMMITTED_ARTIFACT = path.join(
	EXTENSION_ROOT,
	'src/MediaWiki/RestApi/specs/openapi.fragment.dereferenced.json'
);
const REBUILT_ARTIFACT = path.join( EXTENSION_ROOT, 'specs/openapi.fragment.dereferenced.rebuilt.json' );
const SIBLING_OPENAPI = path.resolve( __dirname, '../../../Wikibase/repo/rest-api/src/openapi.json' );

function registeredRoutePaths() {
	// same merge as RouteHandlersTest: production routes plus the dev-only ones
	const extensionJson = JSON.parse( fs.readFileSync( path.join( EXTENSION_ROOT, 'extension.json' ), 'utf8' ) );
	const devRoutes = JSON.parse(
		fs.readFileSync( path.join( EXTENSION_ROOT, 'src/MediaWiki/RestApi/routes.dev.json' ), 'utf8' )
	);

	return [ ...( extensionJson.RestRoutes || [] ), ...devRoutes ]
		// fragment paths are relative to Wikibase's …/rest.php/wikibase server
		.map( ( route ) => route.path.replace( /^\/wikibase/, '' ) );
}

describe( 'committed dereferenced fragment artifact', () => {
	it( 'is self-contained and documents exactly the WikibaseLexeme REST API routes', () => {
		const artifact = JSON.parse( fs.readFileSync( COMMITTED_ARTIFACT, 'utf8' ) );

		assert.deepEqual( Object.keys( artifact.paths ).sort(), registeredRoutePaths().sort() );
		// the artifact is registered with Wikibase's openapi.json route handler,
		// and nothing at runtime resolves $refs
		assert.ok( !JSON.stringify( artifact ).includes( '"$ref"' ) );
	} );

	it( 'is up to date with its sources', function () {
		if ( !fs.existsSync( SIBLING_OPENAPI ) ) {
			// the extension-only CI checkout cannot rebuild the artifact; the
			// api-testing job has the sibling Wikibase checkout and runs this
			this.skip();
		}

		fs.writeFileSync(
			GENERATED_FRAGMENT,
			execFileSync( 'node', [ FRAGMENT_ENTRY ], { cwd: EXTENSION_ROOT } )
		);
		execFileSync( 'npx', [
			'redocly', 'bundle', GENERATED_FRAGMENT,
			'--dereferenced', '--remove-unused-components',
			'-o', REBUILT_ARTIFACT
		], { cwd: EXTENSION_ROOT } );

		assert.equal(
			fs.readFileSync( REBUILT_ARTIFACT, 'utf8' ),
			fs.readFileSync( COMMITTED_ARTIFACT, 'utf8' ),
			'the committed artifact is stale: run `npm run spec:fragment` and commit the result'
		);
	} );
} );
