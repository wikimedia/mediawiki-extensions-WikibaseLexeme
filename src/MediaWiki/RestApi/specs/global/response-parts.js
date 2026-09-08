/* eslint-env node */
'use strict';

const { wikibaseRef } = require( '../helpers.js' );

// mirrors Wikibase's repo/domains/crud/specs/resources/statements/responses.js
const PropertyValuePairResponseRequired = {
	"properties": {
		"property": {
			"required": [ "id", "data_type" ]
		},
		"value": {
			"required": [ "type" ]
		}
	},
	"required": [ "property", "value" ]
};

const StatementResponseRequired = {
	"allOf": [
		PropertyValuePairResponseRequired,
		{
			"properties": {
				"qualifiers": {
					"items": PropertyValuePairResponseRequired
				},
				"references": {
					"items": {
						"properties": {
							"hash": { "type": "string" },
							"parts": {
								"items": PropertyValuePairResponseRequired
							}
						},
						"required": [ "hash", "parts" ]
					}
				}
			},
			"required": [ "id", "rank", "qualifiers", "references" ]
		}
	]
};

const StatementResponse = {
	"allOf": [
		wikibaseRef( '#/components/schemas/Statement' ),
		StatementResponseRequired
	]
};

module.exports = { StatementResponse };
