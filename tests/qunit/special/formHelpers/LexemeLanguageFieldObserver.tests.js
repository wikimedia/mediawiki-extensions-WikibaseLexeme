/**
 * @license GPL-2.0-or-later
 */
( function ( wb, $, QUnit ) {
	QUnit.module( 'wikibase.lexeme.special.formHelpers.LexemeLanguageFieldObserver' );

	var getMockItemLookup = function () {
			return {
				fetchEntity: function () {
					var d = $.Deferred();
					d.resolve( {} );
					return d;
				}
			};
		},
		getMockLanguageExtractor = function ( language ) {
			return {
				getLanguageFromItem: function () {
					return language;
				}
			};
		};

	QUnit.test( 'Given not all parameters provided, throws error', function ( assert ) {
		var LanguageFieldObserver = wb.lexeme.special.formHelpers.LexemeLanguageFieldObserver;

		assert.throws( function () {
			new LanguageFieldObserver( null, getMockItemLookup(), getMockLanguageExtractor() );
		} );
		assert.throws( function () {
			new LanguageFieldObserver( $( '<div/>' ), null, getMockLanguageExtractor() );
		} );
		assert.throws( function () {
			new LanguageFieldObserver( $( '<div/>' ), getMockItemLookup(), null );
		} );
	} );

	QUnit.test( 'Given the provided item has no language code statement, lemma language field is shown', function ( assert ) {
		var $field = $( '<div/>' ).css( 'display', 'none' ),
			languageFieldObserver = new wb.lexeme.special.formHelpers.LexemeLanguageFieldObserver(
				$field,
				getMockItemLookup(),
				getMockLanguageExtractor()
			);

		$field.appendTo( 'body' );

		assert.equal( $field.css( 'display' ), 'none' );
		languageFieldObserver.notify( 'Q123' );
		assert.equal( $field.css( 'display' ), 'block' );

		$field.remove();
	} );

	QUnit.test( 'Given the provided item has a language code statement, lemma language field is hidden', function ( assert ) {
		var $field = $( '<div><input/></div>' ),
			languageFieldObserver = new wb.lexeme.special.formHelpers.LexemeLanguageFieldObserver(
				$field,
				getMockItemLookup(),
				getMockLanguageExtractor( 'en' )
			);

		languageFieldObserver.notify( 'Q123' );
		assert.equal( $field.css( 'display' ), 'none' );
		assert.equal( $field.find( 'input' ).val(), 'en' );
	} );

}( wikibase, jQuery, QUnit ) );
