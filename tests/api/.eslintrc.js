'use strict';

module.exports = {
	extends: [
		'wikimedia',
		'wikimedia/node',
		'wikimedia/language/es2022',
		'wikimedia/mocha'
	],
	root: true,
	rules: {
		"camelcase": [ "error", { allow: [ "data_type", "lexical_category" ] } ],
		"mocha/no-setup-in-describe": 0,
		"max-len": [ "warn", { code: 130 } ]
	}

};
