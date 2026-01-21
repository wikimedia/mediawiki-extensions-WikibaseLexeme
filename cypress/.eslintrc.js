module.exports = {
	extends: [
		'plugin:cypress/recommended',
		'plugin:chai-friendly/recommended'
	],
	rules: {
		'cypress/unsafe-to-chain-command': 'warn'
	}
};
