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

module.exports = { MediawikiEdit };
