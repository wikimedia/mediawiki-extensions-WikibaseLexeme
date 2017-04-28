/**
 * @license GPL-2.0+
 */
( function ( $, wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.datamodel.LexemeForm' );

	var newForm = function ( id, representation ) {
		return new wb.lexeme.datamodel.LexemeForm( id, representation );
	};

	QUnit.test( 'getId()', function ( assert ) {
		var expectedId = 'L123',
			form = newForm( expectedId, 'foo' );

		assert.equal( form.getId(), expectedId );
	} );

	QUnit.test( 'getRepresentation()', function ( assert ) {
		var expectedRepresentation = 'foo',
			form = newForm( 'L123', expectedRepresentation );

		assert.equal( form.getRepresentation(), expectedRepresentation );
	} );

	QUnit.test( 'equals()', function ( assert ) {
		var id = 'L123',
			representation = 'foo',
			form = newForm( id, representation ),
			comparison = newForm( id, representation );

		assert.equal( form.equals( comparison ), true );
	} );

	QUnit.test( 'not equals()', function ( assert ) {
		var id = 'L123',
			representation = 'foo',
			form = newForm( id, representation ),
			equalsDataProvider = [
				{
					comparison: newForm( id, 'bar' ),
					message: 'same id, different representation'
				},
				{
					comparison: newForm( 'L234', representation ),
					message: 'different id, same representation'
				},
				{
					comparison: newForm( 'L234', 'bar' ),
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
