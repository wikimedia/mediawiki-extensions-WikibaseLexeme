/**
 * @license GPL-2.0+
 */
( function ( wb, $, QUnit, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore' );

	/** @type {wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore} */
	var newLemmaWidgetStore = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	var mutations = newLemmaWidgetStore( {}, [], '', 0 ).mutations;

	QUnit.test( 'mutation startSaving switches the isSaving flag to true', function ( assert ) {
		var state = { isSaving: false };

		mutations.startSaving( state );

		assert.ok( state.isSaving );
	} );

	QUnit.test( 'mutation finishSaving switches the isSaving flag to false', function ( assert ) {
		var state = { isSaving: true };

		mutations.finishSaving( state );

		assert.notOk( state.isSaving );
	} );

	QUnit.test( 'mutation updateRevisionId changes baseRevId to given value', function ( assert ) {
		var state = { baseRevId: 1 };

		mutations.updateRevisionId( state, 2 );

		assert.equal( 2, state.baseRevId );
	} );

	function newTestAction( assert ) {

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
					assert.notOk( error );
				} );
			},

			test: function testAction( action, payload, state, expectedMutations ) {
				var done = assert.async();
				var count = 0;

				// mock commit
				function commit( type, payload ) {
					var mutation = expectedMutations[ count ];

					try {
						assert.equal( mutation.type, type, 'Mutation has correct type' );
						// expect( mutation.type ).to.equal( type );
						if ( payload ) {
							assert.deepEqual(
								mutation.payload,
								payload,
								'Mutation was called with correct payload'
							);
							// expect( mutation.payload ).to.deep.equal( payload );
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
					assert.equal( 0, count );
					// expect( count ).to.equal( 0 );
					done();
				}
			}
		};
	}

	QUnit.test(
		'action save on success mutates the state to start saving, updates revision, updates lemmas and finishes saving',
		function ( assert ) {
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
					return $.Deferred().resolve( response ).promise();
				}
			};

			var actions = newLemmaWidgetStore( repoApi, [], '', 0 ).actions;

			newTestAction( assert ).test(
				actions.save,
				[ new Lemma( 'lemma1', 'en' ) ],
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

	QUnit.test(
		'action save calls API with correct parameters and changes state using data from response',
		function ( assert ) {
			var done = assert.async();
			var baseRevisionId = 0;
			var state = { isSaving: false, baseRevId: baseRevisionId, lemmas: [] };

			var newRevisionId = 2;

			var entityId = 'Q1';
			var response = {
				entity: {
					lastrevid: newRevisionId,
					lemmas: [ { value: 'lemma1', language: 'en' } ]
				}
			};

			var editEntity = function ( id, baseRevId, data, clear ) {
				return $.Deferred().resolve( response ).promise();
			};
			var repoApi = {
				editEntity: this.spy( editEntity )
			};

			var lemmasToSave = [ new Lemma( 'lemma1', 'en' ) ];

			var actions = newLemmaWidgetStore( repoApi, [], entityId, baseRevisionId ).actions;
			newTestAction( assert ).applyWithMutations(
				actions.save,
				lemmasToSave,
				state,
				mutations
			).then( function () {
				assert.equal( newRevisionId, state.baseRevId );
				assert.deepEqual( [ { value: 'lemma1', language: 'en' } ], state.lemmas );
				assert.notOk( state.isSaving );

				sinon.assert.calledWith( repoApi.editEntity, entityId, baseRevisionId, { lemmas: lemmasToSave }, false );
				done();
			} );
		}
	);

	QUnit.test(
		'action save calls API with correct parameters when removing an item from the state',
		function ( assert ) {
			var done = assert.async();
			var baseRevisionId = 1;
			var state = { isSaving: false, baseRevId: baseRevisionId, lemmas: [ new Lemma( 'a lemma', 'en' ) ] };

			var newRevisionId = 2;

			var entityId = 'Q1';
			var response = {
				entity: {
					lastrevid: newRevisionId,
					lemmas: []
				}
			};

			var editEntity = function ( id, baseRevId, data, clear ) {
				return $.Deferred().resolve( response ).promise();
			};
			var repoApi = {
				editEntity: this.spy( editEntity )
			};

			var lemmasToSave = [];
			var lemmasInApiRequest = [ { language: 'en', remove: '' } ];

			var actions = newLemmaWidgetStore( repoApi, [], entityId, baseRevisionId ).actions;
			newTestAction( assert ).applyWithMutations(
				actions.save,
				lemmasToSave,
				state,
				mutations
			).then( function () {
				sinon.assert.calledWith( repoApi.editEntity, entityId, baseRevisionId, { lemmas: lemmasInApiRequest }, false );
				done();
			} );
		}
	);

}( wikibase, jQuery, QUnit, sinon ) );
