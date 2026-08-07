/* eslint-env node */
'use strict';

const { wikibaseRef } = require( '../helpers.js' );

// Real data from Wikidata's L42 ("answer"), trimmed to one form and one sense
const lexemeExample = {
	"id": "L42",
	"lemmas": { "en": "answer" },
	"lexical_category": "Q1084",
	"language": "Q1860",
	"statements": { "P5402": [ {
		"id": "L42$06e1e5e0-42ee-f89d-6780-2131e657e121",
		"rank": "normal",
		"property": { "id": "P5402", "data_type": "wikibase-lexeme" },
		"value": { "type": "value", "content": "L961" },
		"qualifiers": [],
		"references": []
	} ] },
	"forms": [ {
		"id": "L42-F1",
		"representations": { "en": "answer" },
		"grammatical_features": [ "Q110786" ],
		"statements": { "P443": [ {
			"id": "L42-F1$885f84f6-4d51-3331-5afb-a8edb72c24fc",
			"rank": "normal",
			"property": { "id": "P443", "data_type": "commonsMedia" },
			"value": { "type": "value", "content": "En-us-answer.ogg" },
			"qualifiers": [ {
				"property": { "id": "P5237", "data_type": "wikibase-item" },
				"value": { "type": "value", "content": "Q7976" }
			} ],
			"references": []
		} ] }
	} ],
	"senses": [ {
		"id": "L42-S1",
		"glosses": { "en": "reply; reaction to a question" },
		"statements": { "P5137": [ {
			"id": "L42-S1$6d0f034a-455c-0b68-3020-208a5c969435",
			"rank": "normal",
			"property": { "id": "P5137", "data_type": "wikibase-item" },
			"value": { "type": "value", "content": "Q1920566" },
			"qualifiers": [],
			"references": []
		} ] }
	} ]
};

module.exports = {
	"Lexeme": {
		"description": "A single Lexeme",
		"headers": {
			"ETag": wikibaseRef( '#/components/headers/ETag' ),
			"Last-Modified": wikibaseRef( '#/components/headers/Last-Modified' ),
			"X-Authenticated-User": wikibaseRef( '#/components/headers/X-Authenticated-User' )
		},
		"content": {
			"application/json": {
				"schema": { "$ref": "#/components/schemas/Lexeme" },
				"example": lexemeExample
			}
		}
	},
	"LexemeNotFound": {
		"description": "The requested Lexeme was not found",
		"content": {
			"application/json": {
				"schema": {
					"type": "object",
					"properties": {
						"code": { "type": "string" },
						"message": { "type": "string" }
					},
					"required": [ "code", "message" ]
				},
				"examples": {
					"lexeme-not-found": {
						"value": {
							"code": "lexeme-not-found",
							"message": "The requested lexeme does not exist"
						}
					}
				}
			}
		},
		"headers": {
			"Content-Language": {
				"description": "Language code of the language in which error message is provided",
				"schema": { "type": "string" },
				"required": true
			}
		}
	}
};
