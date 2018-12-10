( function ( QUnit, valueview ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'wikibase.lexeme.experts.Form' );

	testExpert( {
		expertConstructor: require( 'wikibase.experts.Form' )
	} );

}( QUnit, $.valueview ) );
