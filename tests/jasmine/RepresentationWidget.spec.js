describe( 'RepresentationWidget', function () {
	var expect = require( 'unexpected' ).clone(),
		RepresentationWidget = require( './../../resources/widgets/RepresentationWidget.js' ),
		LexemeStore = require( './../../resources/store/index.js' );

	var mw = { messages: {} };

	var FIRST_ENGLISH_LEMMA = { language: 'en', value: 'color' };
	var SECOND_ENGLISH_LEMMA = { language: 'en-GB', value: 'colour' };
	var EMPTY_REPRESENTATION = { language: '', value: '' };
	var EMPTY_REPRESENTATION_WITH_LEMMA_LANGUAGE = { language: 'en', value: '' };
	var SOME_REPRESENTATION = { language: 'en', value: 'representation in english' };

	it( 'adds a new empty representation when editing the widget with no representations and multiple lemmas', function () {
		var widget = newWidget( [], [ FIRST_ENGLISH_LEMMA, SECOND_ENGLISH_LEMMA ] );

		widget.edit();

		expect( widget.representations, 'to equal', [ EMPTY_REPRESENTATION ] );
	} );

	it( 'adds a new representation with lemma language when editing the widget with no representations and one lemma', function () {
		var widget = newWidget( [], [ FIRST_ENGLISH_LEMMA ] );

		widget.edit();

		expect( widget.representations, 'to equal', [ EMPTY_REPRESENTATION_WITH_LEMMA_LANGUAGE ] );
	} );

	it( 'shows only the representation it contains when editing the widget with some representation', function () {
		var widget = newWidget( [ SOME_REPRESENTATION ] );

		widget.edit();

		expect( widget.representations, 'to equal', [ SOME_REPRESENTATION ] );
	} );

	it( 'can carry redundant representations', function () {
		var REDUNDANT_REPRESENTATION_LANGUAGE = { language: 'en', value: 'foo' };
		var widget = newWidget( [ SOME_REPRESENTATION, REDUNDANT_REPRESENTATION_LANGUAGE ] );

		widget.edit();

		expect( widget.representations, 'to equal', [ SOME_REPRESENTATION, REDUNDANT_REPRESENTATION_LANGUAGE ] );
	} );

	it( 'detects redundant representation languages and can mark the individual languages', function () {
		var REDUNDANT_REPRESENTATION_LANGUAGE = { language: 'en', value: 'foo' };
		var widget = newWidget( [ SOME_REPRESENTATION, REDUNDANT_REPRESENTATION_LANGUAGE ] );

		widget.edit();

		expect( widget.isRedundantLanguage( 'en' ), 'to be true' );
		expect( widget.isRedundantLanguage( 'fr' ), 'to be false' );
	} );

	it( 'detects redundant representation languages and marks the widget', function () {
		var REDUNDANT_REPRESENTATION_LANGUAGE = { language: 'en', value: 'foo' };
		var widget = newWidget( [ SOME_REPRESENTATION, REDUNDANT_REPRESENTATION_LANGUAGE ] );

		widget.edit();

		expect( widget.hasRedundantLanguage, 'to be true' );
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

	it( 'adds a representation with unique lemmas language on add after delete', function () {
		var widget = newWidget( [ SOME_REPRESENTATION ], [ FIRST_ENGLISH_LEMMA ] );

		widget.edit();
		widget.remove( SOME_REPRESENTATION );
		widget.add();

		expect( widget.representations, 'to equal', [ EMPTY_REPRESENTATION_WITH_LEMMA_LANGUAGE ] );
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

	function newWidget( representations, lemmas ) {
		var widget = RepresentationWidget.newComponent(
			getFormIndex(),
			'<div id="dummy-template"></div>',
			function () {
			},
			mw
		);
		return Vue.createApp( widget )
			.use( getTestStore( lemmas, representations ) )
			.mount( document.createElement( 'div' ) );
	}

	function getTestStore( lemmas, representations ) {
		return getStore(
			lemmas || [ FIRST_ENGLISH_LEMMA, SECOND_ENGLISH_LEMMA ],
			getFormIndex(),
			'L42-F1',
			representations
		);
	}

	function getFormIndex() {
		return 0;
	}

	/**
	 * @see lexemeformview::getStore()
	 */
	function getStore( lemmas, formIndex, formId, representations ) {
		var forms = {};
		forms[ formIndex ] = {
			id: formId,
			representations: representations
		};

		return LexemeStore.create( lemmas, forms );
	}

} );
