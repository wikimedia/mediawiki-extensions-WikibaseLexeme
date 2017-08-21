describe( 'wikibase.lexeme.widgets.RepresentationWidget', function () {
	var expect = require( 'unexpected' ).clone();
	var mediaWiki = { messages: {} };

	var RepresentationWidget = require( 'wikibase.lexeme.widgets.RepresentationWidget' );

	var EMPTY_REPRESENTATION = { language: '', value: '' };
	var SOME_REPRESENTATION = { language: 'en', value: 'representation in english' };

	it( 'adds a new empty representation when editing the widget with no representations', function () {
		var widget = newWidget( [] );

		widget.edit();

		expect( widget.representations, 'to equal', [ EMPTY_REPRESENTATION ] );
	} );

	it( 'shows only the representation it contains when editing the widget with some representation', function () {
		var widget = newWidget( [ SOME_REPRESENTATION ] );

		widget.edit();

		expect( widget.representations, 'to equal', [ SOME_REPRESENTATION ] );
	} );

	it( 'is not in edit mode after being created', function () {
		var widget = newWidget( [] );

		expect( widget.inEditMode, 'to be', false );
	} );

	it( 'switches to edit mode when editing', function () {
		var widget = newWidget( [] );

		widget.edit();

		expect( widget.inEditMode, 'to be', true );
	} );

	it( 'is not in edit mode after editing is stopped', function () {
		var widget = newWidget( [] );

		widget.edit();
		widget.stopEditing();

		expect( widget.inEditMode, 'to be', false );
	} );

	it( 'adds an empty representation on add', function () {
		var widget = newWidget(
			[ SOME_REPRESENTATION ]
		);

		widget.edit();
		widget.add();

		expect( widget.representations[ 1 ], 'to equal', EMPTY_REPRESENTATION );
	} );

	it( 'can remove a representation', function () {
		var widget = newWidget(
			[ SOME_REPRESENTATION ]
		);

		widget.edit();
		widget.remove( SOME_REPRESENTATION );

		expect( widget.representations, 'to equal', [] );
	} );

	it( 'cannot add representation if not in edit mode', function () {
		var widget = newWidget( [] );

		expect( function () {
			widget.add();
		}, 'to throw' );
	} );

	it( 'cannot remove representation if not in edit mode', function () {
		var widget = newWidget(
			[ SOME_REPRESENTATION ]
		);

		expect( function () {
			widget.remove( SOME_REPRESENTATION );
		}, 'to throw' );
	} );

	function newWidget( representations ) {
		return RepresentationWidget.create(
			representations,
			document.createElement( 'div' ),
			'<div id="dummy-template"></div>',
			function () {
			},
			mediaWiki
		);
	}
} );
