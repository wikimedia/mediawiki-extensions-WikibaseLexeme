'use strict';

module.exports = {
	"post": {
		"operationId": "addLexemeForm",
		"tags": [ "lexemes" ],
		"summary": "Add a Form to a Lexeme",
		"parameters": [ { "$ref": "#/components/parameters/LexemeId" } ],
		"responses": {
			"201": { "description": "The newly added Form" },
			"400": { "description": "The request cannot be processed" }
		}
	}
};
