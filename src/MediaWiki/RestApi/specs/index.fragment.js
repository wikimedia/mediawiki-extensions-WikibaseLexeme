/* eslint-env node */
'use strict';

const openapi = {
	"openapi": "3.1.0",
	"info": { "title": "WikibaseLexeme REST API fragment", "version": "0.1" },
	"tags": require( './global/tags.js' ),
	"paths": {
		"/v0/entities/lexemes": require( './resources/lexemes/list.js' ),
		"/v0/entities/lexemes/{lexeme_id}": require( './resources/lexemes/single.js' ),
		"/v0/entities/lexemes/{lexeme_id}/statements": require( './resources/lexemes/statements/list.js' )
	},
	"components": {
		"responses": require( './global/responses.js' ),
		"schemas": require( './global/schemas.js' )
	}
};

// export the definition for use in other modules (useful in mocha tests and helpers, for example)
module.exports = { openapi };

if ( require.main === module ) {
	// If executed directly, output the OpenAPI fragment as JSON.
	// This is used in the "spec:fragment:generate" script as Redocly's input.
	console.log( JSON.stringify( openapi, null, 2 ) ); // eslint-disable-line no-console
}
