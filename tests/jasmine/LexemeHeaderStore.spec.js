/**
 * @license GPL-2.0+
 */
describe( 'wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore', function () {
	var sinon = require( 'sinon' );
	var expect = require( 'unexpected' ).clone();

	/** @type {wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore} */
	var newLexemeHeaderStore = require( 'wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	var mutations = newLexemeHeaderStore( {}, [], '', 0 ).mutations;

	it( 'mutation startSaving switches the isSaving flag to true', function () {
		var state = { isSaving: false };

		mutations.startSaving( state );

		expect( state.isSaving, 'to be ok' );
	} );

	it( 'mutation finishSaving switches the isSaving flag to false', function () {
		var state = { isSaving: true };

		mutations.finishSaving( state );

		expect( state.isSaving, 'not to be ok' );
	} );

	it( 'mutation updateRevisionId changes baseRevId to given value', function () {
		var state = { baseRevId: 1 };

		mutations.updateRevisionId( state, 2 );

		expect( state.baseRevId, 'to be', 2 );
	} );

	function newTestAction( done ) {

		// helper for testing action with expected mutations
		return {
			applyWithMutations: function ( action, payload, state, mutations ) {
				function commit( type, payload ) {
					mutations[ type ]( state, payload );
				}

				return action(
					{ commit: commit, state: state },
					payload
				).catch( function ( error ) {
					expect( error, 'not to be ok' );
				} );
			},

			test: function testAction( action, payload, state, expectedMutations ) {
				var count = 0;

				// mock commit
				function commit( type, payload ) {
					var mutation = expectedMutations[ count ];

					try {
						expect( mutation.type, 'to equal', type );
						if ( payload ) {
							expect( mutation.payload, 'to equal', payload );
						}
					} catch ( error ) {
						done( error );
					}

					count++;
					if ( count >= expectedMutations.length ) {
						done();
					}
				}

				// call the action with mocked store and arguments
				action( { commit: commit, state: state }, payload );

				// check if no mutations should have been dispatched
				if ( expectedMutations.length === 0 ) {
					expect( count, 'to equal', 0 );
					done();
				}
			}
		};
	}

	it(
		'action save on success mutates the state to start saving, updates revision, updates lemmas and finishes saving',
		function ( done ) {
			var state = { isSaving: false, baseRevId: 1, lemmas: [] };

			var newRevisionId = 2;
			var response = {
				entity: {
					lastrevid: newRevisionId,
					lemmas: [ { value: 'lemma1', language: 'en' } ]
				}
			};

			var repoApi = {
				editEntity: function ( id, baseRevId, data, clear ) {
					return Promise.resolve( response );
				}
			};

			var actions = newLexemeHeaderStore( repoApi, [], '', 0 ).actions;

			newTestAction( done ).test(
				actions.save,
				{ lemmas: [ new Lemma( 'lemma1', 'en' ) ] },
				state,
				[
					{ type: 'startSaving' },
					{ type: 'updateRevisionId', payload: newRevisionId },
					{ type: 'updateLemmas', payload: [ { value: 'lemma1', language: 'en' } ] },
					{ type: 'finishSaving' }
				]
			);
		}
	);

	it(
		'action save calls API with correct parameters and changes state using data from response',
		function ( done ) {
			var baseRevisionId = 0;
			var entityId = 'L1';
			var state = { id: entityId, isSaving: false, baseRevId: baseRevisionId, lemmas: [] };

			var newRevisionId = 2;

			var response = {
				entity: {
					lastrevid: newRevisionId,
					lemmas: [ { value: 'lemma1', language: 'en' } ]
				}
			};

			var editEntity = function ( id, baseRevId, data, clear ) {
				return Promise.resolve( response );
			};
			var repoApi = {
				editEntity: sinon.spy( editEntity )
			};

			var lexemeToSave = {
				lemmas: [ new Lemma( 'lemma1', 'en' ) ],
				language: 'Q123',
				lexicalCategory: 'Q234'
			};

			var actions = newLexemeHeaderStore( repoApi, { id: entityId }, baseRevisionId ).actions;
			newTestAction( done ).applyWithMutations(
				actions.save,
				lexemeToSave,
				state,
				mutations
			).then( function () {
				expect( newRevisionId, 'to equal', state.baseRevId );
				expect( [ { value: 'lemma1', language: 'en' } ], 'to equal', state.lemmas );
				expect( state.isSaving, 'not to be ok' );

				sinon.assert.calledWith( repoApi.editEntity, entityId, baseRevisionId, lexemeToSave, false );
				done();
			} );
		}
	);

	it(
		'action save calls API with correct parameters when removing an item from the state',
		function ( done ) {
			var baseRevisionId = 1;
			var entityId = 'L1';
			var state = { id: entityId, isSaving: false, baseRevId: baseRevisionId, lemmas: [ new Lemma( 'a lemma', 'en' ) ] };

			var newRevisionId = 2;

			var response = {
				entity: {
					lastrevid: newRevisionId,
					lemmas: []
				}
			};

			var editEntity = function ( id, baseRevId, data, clear ) {
				return Promise.resolve( response );
			};
			var repoApi = {
				editEntity: sinon.spy( editEntity )
			};

			var lexemeToSave = {
				lemmas: [],
				language: 'Q123',
				lexicalCategory: 'Q234'
			};
			var lexemeInApiRequest = {
				lemmas: [ { language: 'en', remove: '' } ],
				language: 'Q123',
				lexicalCategory: 'Q234'
			};

			var actions = newLexemeHeaderStore( repoApi, { id: entityId }, baseRevisionId ).actions;
			newTestAction( done ).applyWithMutations(
				actions.save,
				lexemeToSave,
				state,
				mutations
			).then( function () {
				sinon.assert.calledWith( repoApi.editEntity, entityId, baseRevisionId, lexemeInApiRequest, false );
				done();
			} );
		}
	);
} );
