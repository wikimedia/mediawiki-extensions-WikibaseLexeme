( function ( wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.Lexeme' );

	/** @type {wikibase.lexeme.datamodel.Lexeme} */
	var Lexeme = wb.lexeme.datamodel.Lexeme;
	/** @type {wikibase.lexeme.datamodel.Form} */
	var Form = wb.lexeme.datamodel.Form;

	QUnit.test( 'Can create with ID and get it', function ( assert ) {
		var lexeme = new Lexeme( 'L1' );

		assert.equal( lexeme.getId(), 'L1' );
	} );

	QUnit.test( 'Can set statements and get them back', function ( assert ) {
		var statementGroupSet = createStatementGroupWithSingleStatement( 'P1', 'L1$1' );

		var lexeme = new Lexeme( 'L1', undefined, statementGroupSet );

		assert.equal( lexeme.getStatements(), statementGroupSet );
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

}( wikibase, QUnit ) );
