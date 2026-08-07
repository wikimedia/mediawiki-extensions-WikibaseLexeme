/* eslint-env node */
'use strict';

const wikibaseOpenApi = '../../../../../Wikibase/repo/rest-api/src/openapi.json';

module.exports = {
	wikibaseRef( pointer ) {
		return { "$ref": wikibaseOpenApi + pointer };
	},
	ITEM_ID_PATTERN: '^Q[1-9]\\d{0,9}$',
	LEXEME_ID_PATTERN: '^L[1-9]\\d{0,9}$',
	FORM_ID_PATTERN: '^L[1-9]\\d*-F[1-9]\\d*$',
	SENSE_ID_PATTERN: '^L[1-9]\\d*-S[1-9]\\d*$'
};
