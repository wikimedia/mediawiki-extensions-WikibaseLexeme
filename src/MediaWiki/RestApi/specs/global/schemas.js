/* eslint-env node */
'use strict';

const {
	wikibaseRef,
	ITEM_ID_PATTERN,
	LEXEME_ID_PATTERN,
	FORM_ID_PATTERN,
	SENSE_ID_PATTERN
} = require( '../helpers.js' );

function termMap( description ) {
	return {
		"description": description,
		"type": "object",
		"additionalProperties": { "type": "string" }
	};
}

function statementMap( description ) {
	return {
		"description": description,
		"type": "object",
		"additionalProperties": {
			"type": "array",
			"items": wikibaseRef( '#/components/schemas/Statement' )
		}
	};
}

const lexemeSchema = {
	"id": {
		"description": "The ID of the Lexeme",
		"type": "string",
		"pattern": LEXEME_ID_PATTERN,
		"readOnly": true
	},
	"lemmas": termMap( "The lemmas of the Lexeme, keyed by language code" ),
	"lexical_category": {
		"description": "The ID of the Item representing the lexical category of the Lexeme",
		"type": "string",
		"pattern": ITEM_ID_PATTERN
	},
	"language": {
		"description": "The ID of the Item representing the language of the Lexeme",
		"type": "string",
		"pattern": ITEM_ID_PATTERN
	},
	"statements": statementMap( "The statements of the Lexeme, keyed by Property ID" ),
	"forms": {
		"description": "The Forms of the Lexeme",
		"type": "array",
		"items": { "$ref": "#/components/schemas/Form" }
	},
	"senses": {
		"description": "The Senses of the Lexeme",
		"type": "array",
		"items": { "$ref": "#/components/schemas/Sense" }
	}
};

module.exports = {
	"Lexeme": {
		"type": "object",
		"properties": lexemeSchema
	},
	"NewLexeme": {
		"description": "A Lexeme to be created.",
		"type": "object",
		"properties": {
			... lexemeSchema,

			// no forms and senses on newly created lexemes yet
			forms: undefined,
			senses: undefined
		},
		"required": [ "lemmas", "lexical_category", "language" ]
	},
	"Form": {
		"type": "object",
		"properties": {
			"id": {
				"description": "The ID of the Form",
				"type": "string",
				"pattern": FORM_ID_PATTERN,
				"readOnly": true
			},
			"representations": termMap( "The representations of the Form, keyed by language code" ),
			"grammatical_features": {
				"description": "The IDs of the Items representing the grammatical features of the Form",
				"type": "array",
				"items": { "type": "string", "pattern": ITEM_ID_PATTERN }
			},
			"statements": statementMap( "The statements of the Form, keyed by Property ID" )
		}
	},
	"Sense": {
		"type": "object",
		"properties": {
			"id": {
				"description": "The ID of the Sense",
				"type": "string",
				"pattern": SENSE_ID_PATTERN,
				"readOnly": true
			},
			"glosses": termMap( "The glosses of the Sense, keyed by language code" ),
			"statements": statementMap( "The statements of the Sense, keyed by Property ID" )
		}
	}
};
