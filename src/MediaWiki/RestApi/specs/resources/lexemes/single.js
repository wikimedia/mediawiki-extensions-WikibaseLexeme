'use strict';

const parameterSets = require( '../../global/parameter-sets.js' );
const { wikibaseRef } = require( '../../helpers.js' );
const { PatchRequest } = require( '../../../../../../../Wikibase/repo/rest-api/specs/shared.js' );

const PatchLexemeRequestContent = {
	"schema": PatchRequest,
	"example": {
		"patch": [
			{ "op": "replace", "path": "/lemmas/en", "value": "answer" }
		],
		"tags": [],
		"bot": false,
		"comment": "Update the English lemma"
	}
};

module.exports = {
	"get": {
		"operationId": "getLexeme",
		"tags": [ "lexemes" ],
		"summary": "Retrieve a single Lexeme by ID",
		"parameters": [
			{ "$ref": "#/components/parameters/LexemeId" },
			...parameterSets.ReadConditionalHeaders,
			wikibaseRef( '#/components/parameters/Authorization' )
		],
		"responses": {
			"200": { "$ref": "#/components/responses/Lexeme" },
			"304": wikibaseRef( '#/components/responses/NotModified' ),
			"308": wikibaseRef( '#/components/responses/MovedPermanently' ),
			"400": wikibaseRef( '#/components/responses/InvalidEntityIdInput' ),
			"404": wikibaseRef( '#/components/responses/ResourceNotFound' ),
			"412": wikibaseRef( '#/components/responses/PreconditionFailedError' ),
			"500": wikibaseRef( '#/components/responses/UnexpectedError' )
		}
	},
	"patch": {
		"operationId": "patchLexeme",
		"tags": [ "lexemes" ],
		"summary": "Change a single Lexeme by ID",
		"parameters": [
			{ "$ref": "#/components/parameters/LexemeId" },
			...parameterSets.EditConditionalHeaders,
			wikibaseRef( '#/components/parameters/Authorization' )
		],
		"requestBody": {
			"required": true,
			"content": {
				"application/json-patch+json": PatchLexemeRequestContent,
				"application/json": PatchLexemeRequestContent
			}
		},
		"responses": {
			"200": { "$ref": "#/components/responses/Lexeme" },
			"400": wikibaseRef( '#/components/responses/InvalidPatch' ),
			"403": wikibaseRef( '#/components/responses/PermissionDenied' ),
			"404": wikibaseRef( '#/components/responses/ResourceNotFound' ),
			"409": { "$ref": "#/components/responses/CannotApplyLexemePatch" },
			"412": wikibaseRef( '#/components/responses/PreconditionFailedError' ),
			"422": { "$ref": "#/components/responses/InvalidPatchedLexeme" },
			"429": wikibaseRef( '#/components/responses/RequestLimitReached' ),
			"500": wikibaseRef( '#/components/responses/UnexpectedError' )
		}
	}
};
