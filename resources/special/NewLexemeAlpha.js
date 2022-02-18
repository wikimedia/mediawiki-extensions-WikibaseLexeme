( function () {
	var createAndMount = require( './new-lexeme-dist/SpecialNewLexeme.cjs.js' );

	var messagesRepository = {
		get: function () {
			return mw.message.apply( mw.message, arguments ).parse();
		},
		getText: function () {
			return mw.message.apply( mw.message, arguments ).text();
		}
	};

	createAndMount(
		{ rootSelector: '#special-newlexeme-root', token: mw.user.tokens.get( 'csrfToken' ) },
		messagesRepository
	);
}() );
