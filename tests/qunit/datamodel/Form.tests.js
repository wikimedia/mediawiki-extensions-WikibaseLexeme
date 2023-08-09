/**
 * @license GPL-2.0-or-later
 */

( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.Form' );

	var Form = wb.lexeme.datamodel.Form;
	var datamodel = require( 'wikibase.datamodel' );
	var TermMap = datamodel.TermMap;
	var Term = datamodel.Term;

	var someRepresentations = new TermMap( { en: new Term( 'en', 'text' ) } );

	QUnit.test(
		'When constructed with representations not of type TermMap throws error',
		function ( assert ) {
			assert.throws( function () {
				new Form( 'F1', {} );
			} );
			assert.throws( function () {
				new Form( 'F1', 'something' );
			} );
		}
	);

	QUnit.test( 'getId()', function ( assert ) {
		var expectedId = 'L123',
			form = new Form( expectedId );

		assert.strictEqual( form.getId(), expectedId );
	} );

	QUnit.test( 'getRepresentations()', function ( assert ) {
		var form = new Form( 'L123', someRepresentations );

		assert.strictEqual( form.getRepresentations(), someRepresentations );
	} );

	QUnit.test( 'equals() respects representations', function ( assert ) {
		var id = 'L123',
			form = new Form( id, new TermMap( { en: new Term( 'en', 'text' ) } ) );

		assert.true(
			form.equals( new Form( id, new TermMap( { en: new Term( 'en', 'text' ) } ) ) ),
			'Equal representations'
		);

		assert.false(
			form.equals( new Form( id, new TermMap( { en: new Term( 'en', 'text1' ) } ) ) ),
			'Different representations'
		);
	} );

	QUnit.test( 'equals() also checks grammatical features', function ( assert ) {
		var id = 'L123',
			form = new Form( id, someRepresentations, [ 'Q1' ] );

		assert.true(
			form.equals( new Form( id, someRepresentations, [ 'Q1' ] ) ),
			'same grammatical features'
		);
		assert.false(
			form.equals( new Form( id, someRepresentations, [] ) ),
			'one form has grammatical features, another doesn`t'
		);
		assert.false(
			form.equals( new Form( id, someRepresentations, [ 'Q2' ] ) ),
			'different grammatical features'
		);
	} );

	QUnit.test( 'not equals()', function ( assert ) {
		var id = 'L123',
			representation = new TermMap( { en: new Term( 'en', 'foo' ) } ),
			form = new Form( id, representation ),
			equalsDataProvider = [
				{
					comparison: new Form( id, new TermMap( { en: new Term( 'en', 'bar' ) } ) ),
					message: 'same id, different representation'
				},
				{
					comparison: new Form( 'L234', representation ),
					message: 'different id, same representation'
				},
				{
					comparison: new Form( 'L234', new TermMap( { en: new Term( 'en', 'bar' ) } ) ),
					message: 'different id, different representation'
				},
				{
					comparison: null,
					message: 'not a Form object'
				}
			];

		equalsDataProvider.forEach( function ( testData ) {
			assert.strictEqual(
				form.equals( testData.comparison ),
				false,
				testData.message
			);
		} );
	} );

}( wikibase ) );
