/* eslint-env node */
'use strict';

const { LEXEME_ID_PATTERN } = require( '../helpers.js' );

module.exports = {
	"LexemeId": {
		"name": "lexeme_id",
		"in": "path",
		"required": true,
		"description": "The ID of the required Lexeme",
		"schema": { "type": "string", "pattern": LEXEME_ID_PATTERN },
		"example": "L42"
	}
};
