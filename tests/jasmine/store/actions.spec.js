describe( 'actions', function () {
	var expect = require( 'unexpected' ).clone(),
		sinon = require( 'sinon' ),
		actions = require( './../../../resources/store/actions.js' ),
		actionTypes = require( './../../../resources/store/actionTypes.js' ),
		mutationTypes = require( './../../../resources/store/mutationTypes.js' ),
		state;

	beforeEach( function () {
		state = {
			lexeme: {
				lemmas: [],
				forms: []
			}
		};
	} );

	it( 'ADD_REPRESENTATION on state having no representations and multiple lemmas mutates to empty values', function () {
		state.lexeme.lemmas = [ { language: 'en', value: 'color' }, { language: 'en-gb', value: 'colour' } ];
		state.lexeme.forms = [
			{ id: 'L31-F1', representations: [] },
			{ id: 'L31-F2', representations: [] }
		];

		var store = {
				commit: sinon.stub(),
				state: state
			},
			payload = { formIndex: 1 };

		actions[ actionTypes.ADD_REPRESENTATION ]( store, payload );

		expect(
			store.commit.withArgs( mutationTypes.ADD_REPRESENTATION, {
				formIndex: 1,
				language: '',
				value: ''
			} ).calledOnce,
			'to be true'
		);
		expect( store.commit.withArgs( mutationTypes.DERIVE_REPRESENTATION_LANGUAGE_FROM_LEMMA ).notCalled, 'to be true' );
	} );

	it( 'ADD_REPRESENTATION on state having existing representation and one lemma mutates to empty values', function () {
		state.lexeme.lemmas = [ { language: 'en', value: 'color' } ];
		state.lexeme.forms = [
			{ id: 'L31-F1', representations: [ { language: 'en-gb', value: 'colour' } ] }
		];

		var store = {
				commit: sinon.stub(),
				state: state
			},
			payload = { formIndex: 0 };

		actions[ actionTypes.ADD_REPRESENTATION ]( store, payload );

		expect(
			store.commit.withArgs( mutationTypes.ADD_REPRESENTATION, {
				formIndex: 0,
				language: '',
				value: ''
			} ).calledOnce,
			'to be true'
		);
		expect( store.commit.withArgs( mutationTypes.DERIVE_REPRESENTATION_LANGUAGE_FROM_LEMMA ).notCalled, 'to be true' );
	} );

	it( 'ADD_REPRESENTATION on state having no representations and one lemma mutates to empty values and derives lemma language', function () {
		state.lexeme.lemmas = [ { language: 'en-gb', value: 'colour' } ];
		state.lexeme.forms = [
			{ id: 'L42-F1', representations: [] }
		];

		var store = {
				commit: sinon.stub(),
				state: state
			},
			payload = { formIndex: 0 };

		actions[ actionTypes.ADD_REPRESENTATION ]( store, payload );

		expect(
			store.commit.withArgs( mutationTypes.ADD_REPRESENTATION, {
				formIndex: 0,
				language: '',
				value: ''
			} ).calledOnce,
			'to be true'
		);
		expect(
			store.commit.withArgs( mutationTypes.DERIVE_REPRESENTATION_LANGUAGE_FROM_LEMMA, {
				formIndex: 0,
				representationIndex: 0
			} ).calledOnce, 'to be true' );
	} );

	it( 'REMOVE_REPRESENTATION delegates to mutation', function () {
		var store = {
				commit: sinon.stub()
			},
			payload = { value: 'b' };

		actions[ actionTypes.REMOVE_REPRESENTATION ]( store, payload );

		expect( store.commit.withArgs( mutationTypes.REMOVE_REPRESENTATION, payload ).calledOnce, 'to be true' );
	} );

	it( 'UPDATE_REPRESENTATION_VALUE delegates to mutation', function () {
		var store = {
				commit: sinon.stub()
			},
			payload = { value: 'b' };

		actions[ actionTypes.UPDATE_REPRESENTATION_VALUE ]( store, payload );

		expect( store.commit.withArgs( mutationTypes.UPDATE_REPRESENTATION_VALUE, payload ).calledOnce, 'to be true' );
	} );

	it( 'UPDATE_REPRESENTATION_LANGUAGE delegates to mutation', function () {
		var store = {
				commit: sinon.stub()
			},
			payload = { a: 'b' };

		actions[ actionTypes.UPDATE_REPRESENTATION_LANGUAGE ]( store, payload );

		expect( store.commit.withArgs( mutationTypes.UPDATE_REPRESENTATION_LANGUAGE, payload ).calledOnce, 'to be true' );
	} );

	it( 'REPLACE_ALL_REPRESENTATIONS delegates to mutation', function () {
		var store = {
				commit: sinon.stub()
			},
			payload = { a: 'b' };

		actions[ actionTypes.REPLACE_ALL_REPRESENTATIONS ]( store, payload );

		expect( store.commit.withArgs( mutationTypes.REPLACE_ALL_REPRESENTATIONS, payload ).calledOnce, 'to be true' );
	} );

} );
