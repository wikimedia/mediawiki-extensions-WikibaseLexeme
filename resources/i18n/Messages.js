module.exports = ( function ( mw ) {
	'use strict';

	/**
	 * This module is a wrapper for `mw.messages` introduced to decouple our code from Mediawiki.
	 * Interface is made as simple as possible intentionally to simplify mocking it in tests.
	 *
	 * Important: Most of the components should not require this module directly (except ones that
	 *            deal with wiring), although it is perfectly fine to depend on its interface.
	 */

	/**
	 * @param {string} messageKey
	 * @return {string}
	 */
	function getUnparameterizedTranslation( messageKey ) {
		if ( arguments.length !== 1 ) {
			throw new Error( 'Accepts exactly one argument: key' );
		}

		if ( typeof messageKey !== 'string' ) {
			throw new Error( 'Key should be of type string. `' + typeof messageKey + '` given' );
		}

		if ( !mw.messages.exists( messageKey ) ) {
			throw new Error( 'Message `' + messageKey + '` doesn\'t exist' );
		}

		return mw.messages.get( messageKey );
	}

	/**
	 * @class wikibase.lexeme.i18n.Messages
	 */
	return {
		getUnparameterizedTranslation: getUnparameterizedTranslation
	};
} )( mediaWiki );
