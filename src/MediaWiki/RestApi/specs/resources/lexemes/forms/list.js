'use strict';

const { wikibaseRef } = require( '../../../helpers.js' );
const { MediawikiEdit } = require( '../../../global/request-parts.js' );

module.exports = {
	"post": {
		"operationId": "addLexemeForm",
		"tags": [ "lexemes" ],
		"summary": "Add a new Form to a Lexeme",
		"parameters": [
			{ "$ref": "#/components/parameters/LexemeId" },
			wikibaseRef( '#/components/parameters/IfMatch' ),
			wikibaseRef( '#/components/parameters/IfUnmodifiedSince' ),
			wikibaseRef( '#/components/parameters/IfNoneMatch' ),
			wikibaseRef( '#/components/parameters/Authorization' )
		],
		"requestBody": {
			"description": "Payload containing a Form and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"allOf": [
							{
								"type": "object",
								"properties": {
									"form": { "$ref": "#/components/schemas/NewForm" }
								},
								"required": [ "form" ]
							},
							MediawikiEdit
						]
					},
					"example": {
						"form": {
							"representations": { "en": "answers" },
							"grammatical_features": [ "Q146786" ],
							"statements": {
								"P443": [
									{
										"property": { "id": "P443" },
										"value": { "type": "value", "content": "En-us-answers.ogg" }
									}
								]
							}
						},
						"comment": "Add the plural Form of the English noun \"answer\""
					}
				}
			}
		},
		"responses": {
			"201": { "$ref": "#/components/responses/CreatedLexemeForm" },
			"400": { "$ref": "#/components/responses/InvalidNewFormInput" },
			"403": wikibaseRef( '#/components/responses/PermissionDenied' ),
			"404": wikibaseRef( '#/components/responses/ResourceNotFound' ),
			"409": { "$ref": "#/components/responses/LexemeRedirected" },
			"412": wikibaseRef( '#/components/responses/PreconditionFailedError' ),
			"429": wikibaseRef( '#/components/responses/RequestLimitReached' ),
			"500": wikibaseRef( '#/components/responses/UnexpectedError' )
		}
	}
};
