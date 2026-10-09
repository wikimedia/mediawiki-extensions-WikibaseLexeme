'use strict';

const parameterSets = require( '../../../global/parameter-sets.js' );
const { wikibaseRef } = require( '../../../helpers.js' );
const { MediawikiEdit } = require( '../../../global/request-parts.js' );

module.exports = {
	"post": {
		"operationId": "addLexemeSense",
		"tags": [ "lexemes" ],
		"summary": "Add a new Sense to a Lexeme",
		"parameters": [
			{ "$ref": "#/components/parameters/LexemeId" },
			...parameterSets.EditConditionalHeaders,
			wikibaseRef( '#/components/parameters/Authorization' )
		],
		"requestBody": {
			"description": "Payload containing a Sense and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"allOf": [
							{
								"type": "object",
								"properties": {
									"sense": { "$ref": "#/components/schemas/NewSense" }
								},
								"required": [ "sense" ]
							},
							MediawikiEdit
						]
					},
					"example": {
						"sense": {
							"glosses": { "en": "reply; reaction to a question" },
							"statements": {
								"P5137": [
									{
										"property": { "id": "P5137" },
										"value": { "type": "value", "content": "Q1920566" }
									}
								]
							}
						},
						"comment": "Add a Sense to the English noun \"answer\""
					}
				}
			}
		},
		"responses": {
			"201": { "$ref": "#/components/responses/CreatedLexemeSense" },
			"400": { "$ref": "#/components/responses/InvalidNewSenseInput" },
			"403": wikibaseRef( '#/components/responses/PermissionDenied' ),
			"404": wikibaseRef( '#/components/responses/ResourceNotFound' ),
			"409": { "$ref": "#/components/responses/LexemeRedirected" },
			"412": wikibaseRef( '#/components/responses/PreconditionFailedError' ),
			"429": wikibaseRef( '#/components/responses/RequestLimitReached' ),
			"500": wikibaseRef( '#/components/responses/UnexpectedError' )
		}
	}
};
