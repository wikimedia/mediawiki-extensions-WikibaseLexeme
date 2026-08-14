/* eslint-env node */
'use strict';

const { wikibaseRef } = require( '../../helpers.js' );
const { MediawikiEdit } = require( '../../global/request-parts.js' );

module.exports = {
	"post": {
		"operationId": "addLexeme",
		"tags": [ "lexemes" ],
		"summary": "Create a Lexeme",
		"parameters": [
			wikibaseRef( '#/components/parameters/Authorization' )
		],
		"requestBody": {
			"description": "Payload containing a Lexeme and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"allOf": [
							{
								"type": "object",
								"properties": {
									"lexeme": { "$ref": "#/components/schemas/NewLexeme" }
								},
								"required": [ "lexeme" ]
							},
							MediawikiEdit
						]
					},
					"example": {
						"lexeme": {
							"lemmas": { "en": "answer" },
							"lexical_category": "Q1084",
							"language": "Q1860",
							"statements": {
								"P5402": [
									{
										"property": { "id": "P5402" },
										"value": { "type": "value", "content": "L961" }
									}
								]
							}
						},
						"comment": "Create a Lexeme for the English noun \"answer\""
					}
				}
			}
		},
		"responses": {
			"201": { "$ref": "#/components/responses/CreatedLexeme" },
			"400": { "$ref": "#/components/responses/InvalidNewLexemeInput" },
			"403": wikibaseRef( '#/components/responses/PermissionDenied' ),
			"429": wikibaseRef( '#/components/responses/RequestLimitReached' ),
			"500": wikibaseRef( '#/components/responses/UnexpectedError' )
		}
	}
};
