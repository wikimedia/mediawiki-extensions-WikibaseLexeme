/* eslint-env node */
'use strict';

// mirrors Wikibase's repo/domains/crud/specs/global/request-parts.js
const MediawikiEdit = {
	"type": "object",
	"properties": {
		"tags": {
			"type": "array",
			"items": { "type": "string" },
			"default": []
		},
		"bot": {
			"type": "boolean",
			"default": false
		},
		"comment": { "type": "string" }
	}
};

// mirrors Wikibase's repo/domains/crud/specs/resources/statements/requests.js
const PropertyValuePairRequestRequired = {
	"properties": {
		"property": {
			"required": [ "id" ]
		},
		"value": {
			"required": [ "type" ]
		}
	},
	"required": [ "property", "value" ]
};

const StatementRequestRequired = {
	"allOf": [
		PropertyValuePairRequestRequired,
		{
			"properties": {
				"qualifiers": {
					"items": PropertyValuePairRequestRequired
				},
				"references": {
					"items": {
						"properties": {
							"parts": {
								"items": PropertyValuePairRequestRequired
							}
						},
						"required": [ "parts" ]
					}
				}
			}
		}
	]
};

module.exports = { MediawikiEdit, StatementRequestRequired };
