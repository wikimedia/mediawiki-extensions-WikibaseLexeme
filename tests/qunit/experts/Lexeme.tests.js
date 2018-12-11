( function ( QUnit, valueview ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'wikibase.experts.Lexeme' );

	testExpert( {
		expertConstructor: require( 'wikibase.experts.Lexeme' )
	} );

}( QUnit, $.valueview ) );
