/* eslint-env node, mocha */
'use strict';

const { strict: assert } = require( 'assert' );
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );
const { getRoutePaths } = require( './helpers/restRoutes.js' );

const EXTENSION_ROOT = path.resolve( __dirname, '../..' );
const SIBLING_OPENAPI = path.resolve( __dirname, '../../../Wikibase/repo/rest-api/src/openapi.json' );
const COMBINED_SPEC = path.join( EXTENSION_ROOT, 'specs/openapi-combined.json' );

describe( 'spec:combine (integration)', () => {
	it( 'builds a combined spec with the fragment joined into the sibling Wikibase spec', function () {
		if ( !fs.existsSync( SIBLING_OPENAPI ) ) {
			this.skip();
		}

		execFileSync( 'npm', [ 'run', '-s', 'spec:combine' ], { cwd: EXTENSION_ROOT } );

		const combined = JSON.parse( fs.readFileSync( COMBINED_SPEC, 'utf8' ) );

		const wikibasePaths = Object.keys( JSON.parse( fs.readFileSync( SIBLING_OPENAPI, 'utf8' ) ).paths );
		assert.deepEqual(
			Object.keys( combined.paths ).sort(),
			[ ...wikibasePaths, ...getRoutePaths() ].sort()
		);

		const getResponses = combined.paths[ '/v0/entities/lexemes/{lexeme_id}' ].get.responses;
		assert.equal( typeof getResponses[ '404' ].$ref, 'undefined' );
		assert.equal( typeof getResponses[ '404' ].description, 'string' );

		const responseKeys = Object.keys( combined.components.responses || {} );
		for ( const key of responseKeys ) {
			assert.ok(
				!key.endsWith( '_2' ),
				`components.responses.${ key } looks like a join-generated duplicate`
			);
		}
	} );
} );
