/* eslint-env node */
'use strict';

const { wikibaseRef } = require( '../helpers.js' );
const { StatementResponseRequired } = require( '../../../../../../Wikibase/repo/rest-api/specs/shared.js' );

const StatementResponse = {
	"allOf": [
		wikibaseRef( '#/components/schemas/Statement' ),
		StatementResponseRequired
	]
};

module.exports = { StatementResponse };
