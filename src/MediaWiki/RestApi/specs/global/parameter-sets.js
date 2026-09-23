'use strict';

const { wikibaseRef } = require( '../helpers.js' );

// mirrors Wikibase's repo/domains/crud/specs/global/parameter-sets.js
const ref = ( name ) => wikibaseRef( `#/components/parameters/${ name }` );

module.exports = {
	ReadConditionalHeaders: [ 'IfNoneMatch', 'IfModifiedSince', 'IfMatch', 'IfUnmodifiedSince' ].map( ref ),

	// If-Modified-Since is only defined for GET and HEAD requests. See T318715#8269376.
	EditConditionalHeaders: [ 'IfNoneMatch', 'IfMatch', 'IfUnmodifiedSince' ].map( ref )
};
