/**
 * @license GPL-2.0-or-later
 */
( function ( wb, $, QUnit ) {
	QUnit.module( 'wikibase.lexeme.widgets.LabelDescriptionOptionWidget' );

	QUnit.test( 'getInputLabel: given a label, returns the label', function ( assert ) {
		var widget = newWidget( {
			id: 'Q23',
			label: 'foo'
		} );

		assert.equal( widget.getInputLabel(), 'foo' );
	} );

	QUnit.test( 'getInputLabel: given no label, returns the id', function ( assert ) {
		var widget = newWidget( {
			id: 'Q123'
		} );
		assert.equal( widget.getInputLabel(), 'Q123' );
	} );

	QUnit.test( 'getLabel: given label and description, returns an element containing label and description', function ( assert ) {
		var widget = newWidget( {
			id: 'Q16587531',
			label: 'potato',
			description: 'staple food'
		} );

		assert.equal( widget.getLabel().find( '.label' ).text(), 'potato' );
		assert.equal( widget.getLabel().find( '.description' ).text(), 'staple food' );
	} );

	QUnit.test( 'getLabel: given only a label, contains no description element', function ( assert ) {
		var widget = newWidget( {
			id: 'Q16587531',
			label: 'potato'
		} );
		assert.notOk( widget.getLabel().find( '.description' ).length );
	} );

	QUnit.test( 'getLabel: escapes labels and description', function ( assert ) {
		var labelWithHtml = '<marquee>number of the beast</marquee>',
			descriptionWithHtml = '<i>six hundred sixty six</i>',
			widget = newWidget( {
				id: 'Q666',
				label: labelWithHtml,
				description: descriptionWithHtml
			} );

		assert.equal( widget.getLabel().find( '.label' ).text(), labelWithHtml );
		assert.equal( widget.getLabel().find( '.description' ).text(), descriptionWithHtml );
	} );

	function newWidget( options ) {
		return new wb.lexeme.widgets.LabelDescriptionOptionWidget( options );
	}

}( wikibase, jQuery, QUnit ) );
