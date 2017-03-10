/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit ) {
	QUnit.module( 'wikibase.lexeme.services.LanguageFromItemExtractor' );

	var newLanguageExtractor = function ( propertyId ) {
		return new wb.lexeme.services.LanguageFromItemExtractor( propertyId );
	};

	QUnit.test( 'requires language code property ID', function ( assert ) {
		assert.throws( function () {
			new wb.lexeme.services.LanguageFromItemExtractor();
		} );
	} );

	QUnit.test( 'returns language code given an item serialization with corresponding statement', function ( assert ) {
		var languageExtractor = newLanguageExtractor( 'P123' ),
			language = 'en',
			itemSerialization = {
				claims: {
					'P123': [
						{ mainsnak: { datavalue: { value: language } } }
					]
				}
			};

		assert.equal( languageExtractor.getLanguageFromItem( itemSerialization ), language );
	} );

	QUnit.test( 'returns null given an item serialization that does not contain a language code statment', function ( assert ) {
		var languageExtractor = newLanguageExtractor( 'P123' ),
			itemSerialization = {
				claims: {
					'P234': [
						{ mainsnak: { datavalue: { value: 'en' } } }
					]
				}
			};

		assert.equal( languageExtractor.getLanguageFromItem( itemSerialization ), null );
	} );

	QUnit.test( 'returns null given language code property === null', function ( assert ) {
		var languageExtractor = newLanguageExtractor( null ),
			itemSerialization = {
				claims: {
					'P123': [
						{ mainsnak: { datavalue: { value: 'en' } } }
					]
				}
			};

		assert.equal( languageExtractor.getLanguageFromItem( itemSerialization ), null );
	} );

}( wikibase, jQuery, QUnit ) );
