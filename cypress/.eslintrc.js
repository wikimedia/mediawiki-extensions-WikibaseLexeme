module.exports = {
	extends: [
		'plugin:cypress/recommended',
		'plugin:chai-friendly/recommended'
	],
	parserOptions: {
		sourceType: 'module'
	},
	rules: {
		'comma-dangle': 'off',
		'cypress/unsafe-to-chain-command': 'warn'
	}
}
