( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.Lexeme' );

	var Lexeme = require( '../../../resources/datamodel/Lexeme.js' );
	var datamodel = require( 'wikibase.datamodel' );
	/** @type {wikibase.lexeme.datamodel.Form} */
	var Form = wb.lexeme.datamodel.Form;
	/** @type {wikibase.lexeme.datamodel.Sense} */
	var Sense = wb.lexeme.datamodel.Sense;

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

	QUnit.test( 'Can set statements and get them back', function ( assert ) {
		var lemmas = createTermMapWithTerm();
		var statementGroupSet = createStatementGroupWithSingleStatement( 'P1', 'L1$1' );
		var lexeme = new Lexeme( 'L1', lemmas, statementGroupSet );

		assert.equal( lexeme.getStatements(), statementGroupSet );
	} );

	QUnit.test( 'Can set forms and get them back', function ( assert ) {
		var lemmas = createTermMapWithTerm();
		var forms = [ new Form( 'L1-F1' ) ];
		var statements = null;
		var lexeme = new Lexeme( 'L1', lemmas, statements, forms );

		assert.equal( lexeme.getForms(), forms );
	} );

	QUnit.test( 'Can set senses and get them back', function ( assert ) {
		var senses = [ new Sense( 'L1-S1' ), new Sense( 'L1-S2' ) ];
		var lexeme = new Lexeme( 'L1', createTermMapWithTerm(), null, [], senses );

		assert.equal( lexeme.getSenses(), senses );
	} );

	QUnit.test( 'getSubEntityIds returns all respective ids', function ( assert ) {
		var lexeme = new Lexeme(
			'L1',
			createTermMapWithTerm(),
			null,
			[ new Form( 'L1-F123' ), new Form( 'L1-F124' ) ],
			[ new Sense( 'L1-S3' ), new Sense( 'L1-S4' ) ]
		);

		assert.deepEqual( lexeme.getSubEntityIds(), [ 'L1-F123', 'L1-F124', 'L1-S3', 'L1-S4' ] );
	} );

	QUnit.test( 'getSubEntityIds omits unsaved sub entities with undefined ids', function ( assert ) {
		var lexeme = new Lexeme(
			'L1',
			createTermMapWithTerm(),
			null,
			[ new Form( 'L1-F123' ), new Form() ],
			[ new Sense( 'L1-S3' ), new Sense( 'L1-S4' ) ]
		);

		assert.deepEqual( lexeme.getSubEntityIds(), [ 'L1-F123', 'L1-S3', 'L1-S4' ] );
	} );

	function createStatementGroupWithSingleStatement( propertyId, guid ) {
		return new datamodel.StatementGroupSet( [
			new datamodel.StatementGroup( propertyId, new datamodel.StatementList( [
				new datamodel.Statement( new datamodel.Claim(
					new datamodel.PropertyNoValueSnak( propertyId ), null, guid
				) )
			] ) )
		] );
	}

	function createTermMapWithTerm() {
		return new datamodel.TermMap( { en: new datamodel.Term( 'en', 'foo' ) } );
	}

}( wikibase ) );
