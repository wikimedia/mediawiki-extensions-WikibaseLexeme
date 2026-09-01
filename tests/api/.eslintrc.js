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
		"camelcase": [ "error", { allow: [
			"data_type",
			"denial_context",
			"denial_reason",
			"lexical_category",
			"statement_group_property_id",
			"statement_property_id"
		] } ],
		"mocha/no-setup-in-describe": 0,
		"max-len": [ "warn", { code: 130 } ]
	}

};
