'use strict';

module.exports = {
	"post": {
		"operationId": "addLexemeSense",
		"tags": [ "lexemes" ],
		"summary": "Add a new Sense to a Lexeme",
		"parameters": [ { "$ref": "#/components/parameters/LexemeId" } ],
		"responses": {
			"201": { "description": "The newly added Sense" },
			"400": { "description": "The request cannot be processed" }
		}
	}
};
