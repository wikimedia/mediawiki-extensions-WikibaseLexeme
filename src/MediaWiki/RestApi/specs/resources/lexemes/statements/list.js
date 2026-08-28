/* eslint-env node */
'use strict';

module.exports = {
	"post": {
		"operationId": "addLexemeStatement",
		"tags": [ "lexemes" ],
		"summary": "Add a Statement to a Lexeme",
		"parameters": [
			{
				"name": "lexeme_id",
				"in": "path",
				"required": true,
				"schema": { "type": "string" }
			}
		],
		"responses": {
			"201": { "description": "The newly added Statement" },
			"400": { "description": "The request cannot be processed" }
		}
	}
};
