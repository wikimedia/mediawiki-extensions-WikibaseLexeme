( function ( wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.Lexeme' );

	/** @type {wikibase.lexeme.datamodel.Lexeme} */
	var Lexeme = wb.lexeme.datamodel.Lexeme;
	/** @type {wikibase.lexeme.datamodel.Form} */
	var Form = wb.lexeme.datamodel.Form;

	QUnit.test( 'Can create with ID and get it', function ( assert ) {
		var lemmas = createTermMapWithTerm();
		var lexcat = 'Q1';
		var language = 'Q2';
		var lexeme = new Lexeme( 'L1', lemmas, lexcat, language );

		assert.equal( lexeme.getId(), 'L1' );
	} );

	QUnit.test( 'Can set lemmas and get them back', function ( assert ) {
		var lexemeId = 'L1';
		var lemmas = createTermMapWithTerm();
		var lexcat = 'Q1';
		var language = 'Q2';
		var lexeme = new Lexeme( lexemeId, lemmas, lexcat, language );

		assert.equal( lexeme.getLemmas(), lemmas );
	} );

	QUnit.test( 'Can set lexical catagory and get it', function ( assert ) {
		var lexemeId = 'L1';
		var lemmas = createTermMapWithTerm();
		var lexcat = 'Q123';
		var language = 'Q567';
		var lexeme = new Lexeme( lexemeId, lemmas, lexcat, language );

		assert.equal( lexeme.getLexicalCategory(), lexcat );
	} );

	QUnit.test( 'Can set language and get it', function ( assert ) {
		var lexemeId = 'L1';
		var lemmas = createTermMapWithTerm();
		var lexcat = 'Q123';
		var lang = 'Q1';
		var lexeme = new Lexeme( lexemeId, lemmas, lexcat, lang );

		assert.equal( lexeme.getLanguage(), lang );
	} );

	QUnit.test( 'Can set statements and get them back', function ( assert ) {
		var lemmas = createTermMapWithTerm();
		var lexcat = 'Q123';
		var lang = 'Q1';
		var statementGroupSet = createStatementGroupWithSingleStatement( 'P1', 'L1$1' );
		var lexeme = new Lexeme( 'L1', lemmas, lexcat, lang, statementGroupSet );

		assert.equal( lexeme.getStatements(), statementGroupSet );
	} );

	QUnit.test( 'Can set forms and get them back', function ( assert ) {
		var lemmas = createTermMapWithTerm();
		var lexcat = 'Q123';
		var lang = 'Q1';
		var statements = null;
		var forms = [ new Form( 'L1-F1' ) ];
		var lexeme = new Lexeme( 'L1', lemmas, lexcat, lang, statements, forms );

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
