'use strict';

module.exports = {
	"rules": { },
	"overrides": [
		{
			"files": [ "*.js" ],
			"rules": {
				"brace-style": [ "warn", "1tbs", { "allowSingleLine": true } ],
				"semi": [ "error", "always", { "omitLastInOneLineBlock": true } ],
				// disable so spec content can stay close to its JSON form,
				// matching Wikibase's repo/domains/*/specs/ modules
				"max-len": "off",
				"quotes": "off",
				"quote-props": "off"
			}
		}
	]
};
