'use strict';

const fs = require( 'fs' );
const path = require( 'path' );

const EXTENSION_ROOT = path.resolve( __dirname, '../../..' );
// path items can also hold non-operation fields, e.g. `redocly join` adds `servers` to each
const HTTP_METHODS = [ 'get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace' ];

function getRegisteredEndpoints() {
	const prodRoutes = JSON.parse( fs.readFileSync( path.join( EXTENSION_ROOT, 'src/MediaWiki/RestApi/routes.json' ), 'utf8' ) );
	const devRoutes = JSON.parse(
		fs.readFileSync( path.join( EXTENSION_ROOT, 'src/MediaWiki/RestApi/routes.dev.json' ), 'utf8' )
	);
	return [ ...prodRoutes, ...devRoutes ].map( ( route ) => `${ route.method.toUpperCase() } ${ route.path }` );
}

function getDocumentedEndpoints( spec ) {
	return Object.entries( spec.paths ).flatMap(
		( [ specPath, pathItem ] ) => Object.keys( pathItem )
			.filter( ( key ) => HTTP_METHODS.includes( key ) )
			.map( ( method ) => `${ method.toUpperCase() } ${ specPath }` )
	);
}

module.exports = { getRegisteredEndpoints, getDocumentedEndpoints };
