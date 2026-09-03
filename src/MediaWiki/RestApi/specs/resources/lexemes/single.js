/* eslint-env node */
'use strict';

const { wikibaseRef, LEXEME_ID_PATTERN } = require( '../../helpers.js' );

module.exports = {
	"get": {
		"operationId": "getLexeme",
		"tags": [ "lexemes" ],
		"summary": "Retrieve a single Lexeme by ID",
		"parameters": [
			{
				"name": "lexeme_id",
				"in": "path",
				"required": true,
				"description": "The ID of the required Lexeme",
				"schema": { "type": "string", "pattern": LEXEME_ID_PATTERN },
				"example": "L42"
			},
			wikibaseRef( '#/components/parameters/IfNoneMatch' ),
			wikibaseRef( '#/components/parameters/IfModifiedSince' ),
			wikibaseRef( '#/components/parameters/IfMatch' ),
			wikibaseRef( '#/components/parameters/IfUnmodifiedSince' ),
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
