/* eslint-env node */
'use strict';

// $refs into the sibling Wikibase checkout resolve relative to the generated
// fragment in src/MediaWiki/RestApi/specs/, not relative to this module
const wikibaseOpenApi = '../../../../../Wikibase/repo/rest-api/src/openapi.json';

module.exports = {
	"post": {
		"operationId": "addLexeme",
		"tags": [ "lexemes" ],
		"summary": "Create a Lexeme",
		"requestBody": {
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"type": "object",
						"properties": { "lexeme": { "type": "object" } },
						"required": [ "lexeme" ]
					}
				}
			}
		},
		"responses": {
			"201": {
				"description": "The newly created Lexeme",
				"content": { "application/json": { "schema": { "type": "object" } } }
			},
			"400": { "$ref": wikibaseOpenApi + '#/components/responses/BadRequest' },
			"500": { "$ref": wikibaseOpenApi + '#/components/responses/UnexpectedError' }
		}
	}
};
