'use strict';

const parameterSets = require( '../../../global/parameter-sets.js' );
const { wikibaseRef } = require( '../../../helpers.js' );
const { MediawikiEdit, StatementRequestRequired } = require( '../../../global/request-parts.js' );

module.exports = {
	"post": {
		"operationId": "addLexemeStatement",
		"tags": [ "statements" ],
		"summary": "Add a new Statement to a Lexeme",
		"parameters": [
			{ "$ref": "#/components/parameters/LexemeId" },
			...parameterSets.EditConditionalHeaders,
			wikibaseRef( '#/components/parameters/Authorization' )
		],
		"requestBody": {
			"description": "Payload containing a Wikibase Statement object and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"allOf": [
							{
								"type": "object",
								"properties": {
									"statement": {
										"allOf": [
											wikibaseRef( '#/components/schemas/Statement' ),
											StatementRequestRequired
										]
									}
								},
								"required": [ "statement" ]
							},
							MediawikiEdit
						]
					},
					"example": {
						"statement": {
							"property": { "id": "P5402" },
							"value": { "type": "value", "content": "L961" }
						},
						"comment": "Add the homograph lexeme of the English noun \"answer\""
					}
				}
			}
		},
		"responses": {
			"201": { "$ref": "#/components/responses/CreatedLexemeStatement" },
			"400": wikibaseRef( '#/components/responses/InvalidNewStatementInput' ),
			"403": wikibaseRef( '#/components/responses/PermissionDenied' ),
			"404": wikibaseRef( '#/components/responses/ResourceNotFound' ),
			"409": { "$ref": "#/components/responses/LexemeRedirected" },
			"412": wikibaseRef( '#/components/responses/PreconditionFailedError' ),
			"429": wikibaseRef( '#/components/responses/RequestLimitReached' ),
			"500": wikibaseRef( '#/components/responses/UnexpectedError' )
		}
	}
};
