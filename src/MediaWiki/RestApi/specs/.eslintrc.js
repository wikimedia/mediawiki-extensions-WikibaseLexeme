'use strict';

module.exports = {
	"root": true,
	"ignorePatterns": [
		"openapi.fragment.dereferenced.json",
		"openapi.fragment.generated.json"
	],
	"extends": [
		"wikimedia",
		"wikimedia/node",
		"wikimedia/language/es2022"
	],
	"rules": {
		"no-console": "error",
		// spec parts are required from the sibling Wikibase checkout,
		// which the extension-only CI job does not have
		"n/no-missing-require": "off"
	},
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
