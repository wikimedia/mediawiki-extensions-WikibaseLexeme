/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';
	/** @type {wikibase.lexeme.i18n.Messages} */
	var messages = require( 'wikibase.lexeme.i18n.Messages' );

	QUnit.module( 'wikibase.lexeme.i18n.Messages', function () {
		var EXISTING_MESSAGE_KEY = 'message1';
		var EXISTING_TRANSLATION = 'translation1';

		QUnit.module( 'getUnparameterizedTranslation', {
			beforeEach: function () {
				mw.messages.set( EXISTING_MESSAGE_KEY, EXISTING_TRANSLATION );
			},
			afterEach: function () {
				delete mw.messages.values[ EXISTING_MESSAGE_KEY ];
			}
		}, function () {

			QUnit.test( 'returns translation', function ( assert ) {
				var translation = messages.getUnparameterizedTranslation( EXISTING_MESSAGE_KEY );

				assert.equal( translation, EXISTING_TRANSLATION );
			} );

			QUnit.test( 'accepts strings only', function ( assert ) {
				var invalidMessageKeyValues = [
					null,
					undefined,
					[],
					{},
					function () {
					}
				];
				invalidMessageKeyValues.forEach( function ( key ) {
					assert.throws( function () {
						messages.getUnparameterizedTranslation( key );
					}, typeof key );
				} );
			} );

			QUnit.test( 'doesn\'t accept any arguments except the key', function ( assert ) {
				assert.throws( function () {
					messages.getUnparameterizedTranslation( EXISTING_MESSAGE_KEY, 'some argument' );
				} );
			} );

			QUnit.test( 'throws error if message does not exist', function ( assert ) {
				assert.throws( function () {
					messages.getUnparameterizedTranslation( 'some-message' );
				} );
			} );
		} );
	} );

}( wikibase ) );
