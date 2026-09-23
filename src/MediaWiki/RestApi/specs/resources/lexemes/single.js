'use strict';

const parameterSets = require( '../../global/parameter-sets.js' );
const { wikibaseRef } = require( '../../helpers.js' );

module.exports = {
	"get": {
		"operationId": "getLexeme",
		"tags": [ "lexemes" ],
		"summary": "Retrieve a single Lexeme by ID",
		"parameters": [
			{ "$ref": "#/components/parameters/LexemeId" },
			...parameterSets.ReadConditionalHeaders,
			wikibaseRef( '#/components/parameters/Authorization' )
		],
		"responses": {
			"200": { "$ref": "#/components/responses/Lexeme" },
			"304": wikibaseRef( '#/components/responses/NotModified' ),
			"308": wikibaseRef( '#/components/responses/MovedPermanently' ),
			"400": wikibaseRef( '#/components/responses/InvalidEntityIdInput' ),
			"404": wikibaseRef( '#/components/responses/ResourceNotFound' ),
			"412": wikibaseRef( '#/components/responses/PreconditionFailedError' ),
			"500": wikibaseRef( '#/components/responses/UnexpectedError' )
		}
	}
};
