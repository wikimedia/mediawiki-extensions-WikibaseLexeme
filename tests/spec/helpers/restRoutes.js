/* eslint-env node */
'use strict';

const fs = require( 'fs' );
const path = require( 'path' );

const EXTENSION_ROOT = path.resolve( __dirname, '../../..' );

function getRoutePaths() {
	const extensionJson = JSON.parse( fs.readFileSync( path.join( EXTENSION_ROOT, 'extension.json' ), 'utf8' ) );
	const devRoutes = JSON.parse(
		fs.readFileSync( path.join( EXTENSION_ROOT, 'src/MediaWiki/RestApi/routes.dev.json' ), 'utf8' )
	);
	return [ ...( extensionJson.RestRoutes || [] ), ...devRoutes ]
		.map( ( route ) => route.path.replace( /^\/wikibase/, '' ) );
}

module.exports = { getRoutePaths };
