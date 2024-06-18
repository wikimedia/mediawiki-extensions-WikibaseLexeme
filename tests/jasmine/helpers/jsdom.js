/* eslint no-restricted-globals: 0 */
/**
 * @file
 * Set up a JSDOM environment and install a very limited subset of its globals.
 * Keys for globals to install should only be added here
 * if they are known to be necessary to make the tests pass.
 */
const toRestore = new Map();
const toDelete = new Set();
const document = new ( require( 'jsdom' ).JSDOM )();
for ( const key of [
	'window',
	'document',
	'Element',
	'SVGElement',
	'Event',
] ) {
	if ( key in global ) {
		toRestore.set( key, global[ key ] );
	} else {
		toDelete.add( key );
	}
	global[ key ] = document.window[ key ];
}

/**
 * Reset the JSDOM environment, restoring the globals to their old state
 * (restoring their old value, or deleting them if they did not exist).
 *
 * Jasmine does not give us the means to call this as part of global teardown,
 * so we run it via a custom Grunt action instead (jasmine_nodejs_reset).
 */
global.jsdomGlobalReset = function () {
	toRestore.forEach( ( value, key ) => {
		global[ key ] = value;
	} );
	toDelete.forEach( ( key ) => {
		delete global[ key ];
	} );
	delete global.jsdomGlobalReset;
}
