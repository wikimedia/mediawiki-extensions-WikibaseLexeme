( function ( wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.Lexeme' );

	/** @type {wikibase.lexeme.datamodel.Lexeme} */
	var Lexeme = wb.lexeme.datamodel.Lexeme;
	/** @type {wikibase.lexeme.datamodel.LexemeForm} */
	var LexemeForm = wb.lexeme.datamodel.LexemeForm;

	QUnit.test( 'Can create with ID and get it', function ( assert ) {
		var lexeme = new Lexeme( 'L1' );

		assert.equal( lexeme.getId(), 'L1' );
	} );

	QUnit.test( 'Can set statements and get them back', function ( assert ) {
		var statementGroupSet = createStatementGroupWithSingleStatement( 'P1', 'L1$1' );

		var lexeme = new Lexeme( 'L1', statementGroupSet );

		assert.equal( lexeme.getStatements(), statementGroupSet );
	} );

	QUnit.test( 'Can find existing statement on the Lexeme by GUID', function ( assert ) {
		var guid = 'L1$1';
		var statementGroupSet = createStatementGroupWithSingleStatement( 'P1', guid );

		var lexeme = new Lexeme( 'L1', statementGroupSet );

		assert.ok( lexeme.findStatementByGuid( guid ) instanceof wb.datamodel.Statement );
	} );

	QUnit.test( 'Can`t find nonexistent statement - returns null', function ( assert ) {
		var statementGroupSet = createStatementGroupWithSingleStatement( 'P1', 'L1$existing' );

		var lexeme = new Lexeme( 'L1', statementGroupSet );

		assert.equal( lexeme.findStatementByGuid( 'L1$nonexistent' ), null );
	} );

	QUnit.test(
		'Can find existing statement on LexemeForm through Lexeme by GUID',
		function ( assert ) {
			var lexemeStatementGroupSet = createStatementGroupWithSingleStatement( 'P1', 'L1$1' );
			var formStatementGroupSet = createStatementGroupWithSingleStatement( 'P1', 'F1$1' );
			var lexeme = new Lexeme( 'L1', lexemeStatementGroupSet );
			lexeme.forms = [ new LexemeForm( 'F1', '', [], formStatementGroupSet ) ];

			assert.ok( lexeme.findStatementByGuid( 'F1$1' ) instanceof wb.datamodel.Statement );
		}
	);

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
