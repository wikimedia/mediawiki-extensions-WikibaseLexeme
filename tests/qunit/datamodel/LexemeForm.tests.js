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
			equalsDataProvider = [
				{
					comparison: newForm( id, representation ),
					expectedResult: true,
					message: 'Same id, same representation'
				},
				{
					comparison: newForm( id, 'bar' ),
					expectedResult: false,
					message: 'same id, different representation'
				},
				{
					comparison: newForm( 'L234', representation ),
					expectedResult: false,
					message: 'different id, same representation'
				},
				{
					comparison: newForm( 'L234', 'bar' ),
					expectedResult: false,
					message: 'different id, different representation'
				},
				{
					comparison: null,
					expectedResult: false,
					message: 'not a LexemeForm object'
				}
			];

		equalsDataProvider.forEach( function ( testData ) {
			assert.equal(
				form.equals(testData.comparison),
				testData.expectedResult,
				testData.message
			);
		} );
	} );

}( jQuery, wikibase, QUnit ) );
