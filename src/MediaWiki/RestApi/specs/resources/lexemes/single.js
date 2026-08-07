/* eslint-env node */
'use strict';

// $refs into the sibling Wikibase checkout resolve relative to the generated
// fragment in src/MediaWiki/RestApi/specs/, not relative to this module
const wikibaseOpenApi = '../../../../../Wikibase/repo/rest-api/src/openapi.json';

module.exports = {
	"get": {
		"operationId": "getLexeme",
		"tags": [ "lexemes" ],
		"summary": "Retrieve a single Lexeme by ID",
		"parameters": [ {
			"name": "lexeme_id",
			"in": "path",
			"required": true,
			"description": "The ID of the required Lexeme",
			"schema": { "type": "string" },
			"example": "L42"
		} ],
		"responses": {
			"200": {
				"description": "A single Lexeme",
				"content": { "application/json": { "schema": { "type": "object" } } }
			},
			"400": { "$ref": wikibaseOpenApi + '#/components/responses/InvalidEntityIdInput' },
			"404": { "$ref": wikibaseOpenApi + '#/components/responses/ResourceNotFound' },
			"500": { "$ref": wikibaseOpenApi + '#/components/responses/UnexpectedError' }
		}
	}
};
