( function ( wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.Lexeme' );

	/** @type {wikibase.lexeme.datamodel.Lexeme} */
	var Lexeme = wb.lexeme.datamodel.Lexeme;
	/** @type {wikibase.lexeme.datamodel.Form} */
	var Form = wb.lexeme.datamodel.Form;

	QUnit.test( 'Can create with ID and get it', function ( assert ) {
		var lemmas = createTermMapWithTerm();
		var lexeme = new Lexeme( 'L1', lemmas );

		assert.equal( lexeme.getId(), 'L1' );
	} );

	QUnit.test( 'Can set lemmas and get them back', function ( assert ) {
		var lexemeId = 'L1';
		var lemmas = createTermMapWithTerm();
		var lexeme = new Lexeme( lexemeId, lemmas );

		assert.equal( lexeme.getLemmas(), lemmas );
	} );

	QUnit.test( 'Can set forms and get them back', function ( assert ) {
		var lemmas = createTermMapWithTerm();
		var forms = [ new Form( 'L1-F1' ) ];
		var lexeme = new Lexeme( 'L1', lemmas, forms );

		assert.equal( lexeme.getForms(), forms );
	} );

	function createStatementGroupWithSingleStatement( propertyId, guid ) {
		return new wb.datamodel.StatementGroupSet( [
			new wb.datamodel.StatementGroup( propertyId, new wb.datamodel.StatementList( [
				new wb.datamodel.Statement( new wb.datamodel.Claim(
					new wb.datamodel.PropertyNoValueSnak( propertyId ), null, guid
				) )
			] ) )
		] );
	}

	function createTermMapWithTerm() {
		return new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'foo' ) } );
	}

}( wikibase, QUnit ) );
