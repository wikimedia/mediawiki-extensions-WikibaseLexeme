/**
 * @license GPL-2.0+
 */
( function ( $, wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.LexemeForm' );

	var Form = wb.lexeme.datamodel.LexemeForm;

	QUnit.test( 'getId()', function ( assert ) {
		var expectedId = 'L123',
			form = new Form( expectedId, 'foo' );

		assert.equal( form.getId(), expectedId );
	} );

	QUnit.test( 'getRepresentation()', function ( assert ) {
		var expectedRepresentation = 'foo',
			form = new Form( 'L123', expectedRepresentation );

		assert.equal( form.getRepresentation(), expectedRepresentation );
	} );

	QUnit.test( 'equals()', function ( assert ) {
		var id = 'L123',
			representation = 'foo',
			form = new Form( id, representation ),
			comparison = new Form( id, representation );

		assert.equal( form.equals( comparison ), true );
	} );

	QUnit.test( 'equals() also checks grammatical features', function ( assert ) {
		var id = 'L123',
			representation = 'foo',
			form = new Form( id, representation, [ 'Q1' ] );

		assert.ok(
			form.equals( new Form( id, representation, [ 'Q1' ] ) ),
			'same grammatical features'
		);
		assert.notOk(
			form.equals( new Form( id, representation, [] ) ),
			'one form has grammatical features, another doesn`t'
		);
		assert.notOk(
			form.equals( new Form( id, representation, [ 'Q2' ] ) ),
			'different grammatical features'
		);
	} );

	QUnit.test( 'not equals()', function ( assert ) {
		var id = 'L123',
			representation = 'foo',
			form = new Form( id, representation ),
			equalsDataProvider = [
				{
					comparison: new Form( id, 'bar' ),
					message: 'same id, different representation'
				},
				{
					comparison: new Form( 'L234', representation ),
					message: 'different id, same representation'
				},
				{
					comparison: new Form( 'L234', 'bar' ),
					message: 'different id, different representation'
				},
				{
					comparison: null,
					message: 'not a LexemeForm object'
				}
			];

		equalsDataProvider.forEach( function ( testData ) {
			assert.equal(
				form.equals( testData.comparison ),
				false,
				testData.message
			);
		} );
	} );

}( jQuery, wikibase, QUnit ) );
