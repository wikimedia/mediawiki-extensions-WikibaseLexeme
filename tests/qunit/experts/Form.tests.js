( function ( QUnit, valueview, wb ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'wikibase.lexeme.experts.Form' );

	testExpert( {
		expertConstructor: require( 'wikibase.experts.Form' )
	} );

}( QUnit, jQuery.valueview, wikibase ) );
