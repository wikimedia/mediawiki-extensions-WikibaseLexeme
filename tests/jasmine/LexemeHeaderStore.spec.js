/**
 * @license GPL-2.0-or-later
 */
describe( 'LexemeHeader.newLexemeHeaderStore', function () {
	var sinon = require( 'sinon' );
	var expect = require( 'unexpected' ).clone();

	global.$ = require( 'jquery' ); // eslint-disable-line no-restricted-globals

	var newLexemeHeaderStore = require( './../../resources/widgets/LexemeHeader.newLexemeHeaderStore.js' );
	var Lemma = require( './../../resources/datamodel/Lemma.js' );
	var LemmaList = require( './../../resources/datatransfer/LemmaList.js' );

	var mutations = newLexemeHeaderStore( {}, {}, 0, 'Some language', 'Some category' ).mutations;

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

	it( 'mutation updateLanguage changes language and languageLink to given values', function () {
		var state = { language: 'Q123', languageLink: '<a>English</a>' };

		mutations.updateLanguage( state, { id: 'Q777', link: '<a>Finnish</a>' } );

		expect( state.language, 'to be', 'Q777' );
		expect( state.languageLink, 'to be', '<a>Finnish</a>' );
	} );

	it( 'mutation updateLanguage changes lexical category and the link to given values', function () {
		var state = { language: 'Q123', languageLink: '<a>noun</a>' };

		mutations.updateLanguage( state, { id: 'Q999', link: '<a>verb</a>' } );

		expect( state.language, 'to be', 'Q999' );
		expect( state.languageLink, 'to be', '<a>verb</a>' );
	} );

	it( 'mutation updateLemmas changes lemmas to given values', function () {
		var state = { lemmas: new LemmaList( [ new Lemma( 'foo', 'en' ) ] ) };

		mutations.updateLemmas( state, [ new Lemma( 'Bar', 'de' ) ] );

		expect( state.lemmas.getLemmas()[ 0 ].language, 'to equal', 'de' );
		expect( state.lemmas.getLemmas()[ 0 ].value, 'to equal', 'Bar' );
	} );

	it( 'failed save returns rejected promise with a single error object', function ( done ) {
		var expectedError = { code: 'error-code', info: 'info-text' },
			repoApi = {
				editEntity: function () {
					return $.Deferred( function ( defer ) {
						defer.reject( 'error-code', { error: expectedError } );
					} );
				},
				formatValue: function ( dataValue ) {
					return Promise.resolve( { result: 'Link for ' + dataValue.value.id } );
				}
			},
			store = new Vuex.Store( newLexemeHeaderStore( repoApi, { lemmas: [] }, 0, 'Q123', 'Q321' ) );

		store.dispatch( 'save', {
			lemmas: [ new Lemma( '', '' ) ],
			language: 'Q123',
			lexicalCategory: 'Q321'
		} ).catch( function ( error ) {
			expect( error, 'to equal', expectedError );
			done();
		} );

	} );

	it( 'failed save returns rejected promise with first error object if API returns multiple errors', function ( done ) {
		var expectedError = { code: 'error-code', info: 'info-text' },
			ignoredError = { code: 'error-code', info: 'info-text' },
			repoApi = {
				editEntity: function () {
					return $.Deferred( function ( defer ) {
						defer.reject( 'error-code', { errors: [ expectedError, ignoredError ] } );
					} );
				},
				formatValue: function ( dataValue ) {
					return Promise.resolve( { result: 'Link for ' + dataValue.value.id } );
				}
			},
			store = new Vuex.Store( newLexemeHeaderStore( repoApi, { lemmas: [] }, 0, 'Q223', 'Q322' ) );

		store.dispatch( 'save', {
			lemmas: [ new Lemma( '', '' ) ],
			language: 'Q223',
			lexicalCategory: 'Q322'
		} ).catch( function ( error ) {
			expect( error, 'to equal', expectedError );
			done();
		} );

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
						done.fail( error.getErrorMessage() );
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
		'action save on success mutates the state to start saving, updates state and finishes saving',
		function ( done ) {
			var state = { isSaving: false, baseRevId: 1, lemmas: new LemmaList( [] ) };

			var newRevisionId = 2;
			var response = {
				entity: {
					lastrevid: newRevisionId,
					lemmas: { en: { value: 'lemma1', language: 'en' } },
					language: 'Q123',
					lexicalCategory: 'Q234'
				}
			};

			var repoApi = {
				editEntity: function ( id, baseRevId, data, clear ) {
					return Promise.resolve( response );
				},
				formatValue: function ( dataValue, options, dataType, outputFormat, propertyId ) {
					return Promise.resolve( { result: 'Link for ' + dataValue.value.id } );
				}
			};

			var actions = newLexemeHeaderStore( repoApi, {}, 0, 'Some language', 'Some category' ).actions;

			newTestAction( done ).test(
				actions.save,
				{
					lemmas: [ new Lemma( 'lemma1', 'en' ) ],
					language: 'Q123',
					lexicalCategory: 'Q234'
				},
				state,
				[
					{ type: 'startSaving' },
					{ type: 'updateRevisionId', payload: newRevisionId },
					{ type: 'updateLemmas', payload: { en: { value: 'lemma1', language: 'en' } } },
					{ type: 'updateLanguage', payload: { id: 'Q123', link: 'Link for Q123' } },
					{ type: 'updateLexicalCategory', payload: { id: 'Q234', link: 'Link for Q234' } },
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
			var state = {
				id: entityId,
				isSaving: false,
				baseRevId: baseRevisionId,
				lemmas: new LemmaList( [] ),
				language: 'Q1',
				languageLink: 'Some language',
				lexicalCategory: 'Q2',
				lexicalCategoryLink: 'Some category'
			};

			var newRevisionId = 2;

			var response = {
				entity: {
					lastrevid: newRevisionId,
					lemmas: { en: { value: 'lemma1', language: 'en' } },
					language: 'Q123',
					lexicalCategory: 'Q234'
				}
			};

			var editEntity = function ( id, baseRevId, data, clear ) {
				return Promise.resolve( response );
			};
			var formatValue = function ( dataValue, options, dataType, outputFormat, propertyId ) {
				return Promise.resolve( { result: 'Link for ' + dataValue.value.id } );
			};
			var repoApi = {
				editEntity: sinon.spy( editEntity ),
				formatValue: sinon.spy( formatValue )
			};

			var lexemeToSave = {
				lemmas: [ new Lemma( 'lemma1', 'en' ) ],
				language: 'Q123',
				lexicalCategory: 'Q234'
			};

			var actions = newLexemeHeaderStore( repoApi, { id: entityId, language: 'Q1', lexicalCategory: 'Q2' }, baseRevisionId, 'Some language', 'Some category' ).actions;
			newTestAction( done ).applyWithMutations(
				actions.save,
				lexemeToSave,
				state,
				mutations
			).then( function () {
				expect( newRevisionId, 'to equal', state.baseRevId );
				expect( [ new Lemma( 'lemma1', 'en' ) ], 'to equal', state.lemmas.getLemmas() );
				expect( 'Q123', 'to equal', state.language );
				expect( 'Link for Q123', 'to equal', state.languageLink );
				expect( 'Q234', 'to equal', state.lexicalCategory );
				expect( 'Link for Q234', 'to equal', state.lexicalCategoryLink );
				expect( state.isSaving, 'not to be ok' );

				sinon.assert.calledWith(
					repoApi.editEntity,
					entityId,
					baseRevisionId,
					{
						lemmas: { en: lexemeToSave.lemmas[ 0 ] },
						language: lexemeToSave.language,
						lexicalCategory: lexemeToSave.lexicalCategory
					},
					false
				);
				expect(
					repoApi.formatValue.withArgs(
						{ type: 'wikibase-entityid', value: { id: 'Q123' } },
						{},
						'wikibase-item',
						'text/html',
						''
					).called,
					'to be true'
				);
				expect(
					repoApi.formatValue.withArgs(
						{ type: 'wikibase-entityid', value: { id: 'Q234' } },
						{},
						'wikibase-item',
						'text/html',
						''
					).called,
					'to be true'
				);

				done();
			} );
		}
	);

	// further tests for the lemmas of the API request,
	// without asserting that the state changes according to the response
	[
		{
			description: 'removing an item from the state',
			stateLemmas: new LemmaList( [ new Lemma( 'a lemma', 'en' ) ] ),
			saveLemmas: [],
			requestLemmas: { en: { language: 'en', remove: '' } },
			responseLemmas: {},
		},
		{
			description: 'editing an existing lemma',
			stateLemmas: new LemmaList( [ new Lemma( 'a lemma', 'en' ) ] ),
			saveLemmas: [ new Lemma( 'a different lemma', 'en' ) ],
			requestLemmas: { en: new Lemma( 'a different lemma', 'en' ) },
			responseLemmas: { en: { language: 'en', value: 'a different lemma' } }
		},
		{
			description: 'editing several existing lemmas',
			stateLemmas: new LemmaList( [
				new Lemma( 'a lemma', 'en' ),
				new Lemma( 'ein Lemma', 'de' )
			] ),
			saveLemmas: [
				new Lemma( 'a different lemma', 'en' ),
				new Lemma( 'ein anderes Lemma', 'de' )
			],
			requestLemmas: {
				en: new Lemma( 'a different lemma', 'en' ),
				de: new Lemma( 'ein anderes Lemma', 'de' )
			},
			responseLemmas: {
				en: { language: 'en', value: 'a different lemma' },
				de: { language: 'de', value: 'ein anderes Lemma' }
			}
		},
		{
			description: 'removing one of several existing lemmas',
			stateLemmas: new LemmaList( [
				new Lemma( 'a lemma', 'en' ),
				new Lemma( 'ein Lemma', 'de' )
			] ),
			saveLemmas: [
				new Lemma( 'ein Lemma', 'de' )
			],
			requestLemmas: {
				en: { language: 'en', remove: '' }
				// de unchanged, not sent
			},
			responseLemmas: {
				de: { language: 'de', value: 'ein Lemma' }
			}
		},
		{
			description: 'editing one of several existing lemmas',
			stateLemmas: new LemmaList( [
				new Lemma( 'a lemma', 'en' ),
				new Lemma( 'ein Lemma', 'de' )
			] ),
			saveLemmas: [
				new Lemma( 'a different lemma', 'en' ),
				new Lemma( 'ein Lemma', 'de' )
			],
			requestLemmas: {
				en: new Lemma( 'a different lemma', 'en' )
				// de unchanged, not sent
			},
			responseLemmas: {
				en: { language: 'en', value: 'a different lemma' },
				de: { language: 'de', value: 'ein Lemma' }
			}
		},
		{
			description: 'adding, editing and removing lemmas',
			stateLemmas: new LemmaList( [
				new Lemma( 'a lemma', 'en' ),
				new Lemma( 'ein Lemma', 'de' ),
				new Lemma( 'un lemme', 'fr' )
			] ),
			saveLemmas: [
				new Lemma( 'a different lemma', 'en' ),
				new Lemma( 'ein Lemma', 'de' ),
				new Lemma( 'un lema', 'es' )
			],
			requestLemmas: {
				en: new Lemma( 'a different lemma', 'en' ),
				// de unchanged, not sent
				es: new Lemma( 'un lema', 'es' ),
				fr: { language: 'fr', remove: '' }
			},
			responseLemmas: {
				en: { language: 'en', value: 'a different lemma' },
				de: { language: 'de', value: 'ein Lemma' },
				es: { language: 'es', value: 'un lema' }
			}
		}
	].forEach( function ( testCase ) {
		it(
			'action save calls API with correct parameters when ' + testCase.description,
			function ( done ) {
				var baseRevisionId = 1;
				var entityId = 'L1';
				var state = {
					id: entityId,
					isSaving: false,
					baseRevId: baseRevisionId,
					lemmas: testCase.stateLemmas,
					language: 'Q1',
					languageLink: 'Some language'
				};

				var newRevisionId = 2;

				var response = {
					entity: {
						lastrevid: newRevisionId,
						lemmas: testCase.responseLemmas,
						language: 'Q123',
						lexicalCategory: 'Q234'
					}
				};

				var editEntity = function ( id, baseRevId, data, clear ) {
					return Promise.resolve( response );
				};
				var formatValue = function ( dataValue, options, dataType, outputFormat, propertyId ) {
					return Promise.resolve( { result: 'some formatted item' } );
				};

				var repoApi = {
					editEntity: sinon.spy( editEntity ),
					formatValue: sinon.spy( formatValue )
				};

				var lexemeToSave = {
					lemmas: testCase.saveLemmas,
					language: 'Q123',
					lexicalCategory: 'Q234'
				};
				var lexemeInApiRequest = {
					lemmas: testCase.requestLemmas,
					language: 'Q123',
					lexicalCategory: 'Q234'
				};

				var actions = newLexemeHeaderStore( repoApi, { id: entityId }, baseRevisionId, 'Some language', 'Some category' ).actions;
				newTestAction( done ).applyWithMutations(
					actions.save,
					lexemeToSave,
					state,
					mutations
				).then( function () {
					sinon.assert.calledWith( repoApi.editEntity, entityId, baseRevisionId, lexemeInApiRequest, false );
				} ).then( done, done.fail );
			}
		);
	} );
} );
