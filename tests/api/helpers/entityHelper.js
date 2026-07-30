'use strict';

const { action } = require( 'api-testing' );

async function createLexeme( lexeme ) {
	const anon = await action.getAnon();
	const { entity: { id } } = await anon.action( 'wbeditentity', {
		new: 'lexeme',
		data: JSON.stringify( lexeme ),
		token: await anon.token()
	}, 'POST' );

	return id;
}

async function getLatestEditMetadata( lexemeId ) {
	const editMetadata = ( await action.getAnon().action( 'query', {
		list: 'recentchanges',
		rctitle: `Lexeme:${ lexemeId }`,
		rclimit: 1,
		rcprop: 'tags|flags|comment|ids|timestamp|user'
	} ) ).query.recentchanges[ 0 ];

	return {
		...editMetadata,
		timestamp: new Date( editMetadata.timestamp ).toUTCString()
	};
}

module.exports = {
	createLexeme,
	getLatestEditMetadata
};
