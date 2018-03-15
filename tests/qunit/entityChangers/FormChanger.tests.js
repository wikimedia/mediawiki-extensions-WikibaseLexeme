/**
 * @license GPL-2.0-or-later
 */
( function ( $, wb, QUnit, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.entityChangers.FormChanger' );

	var FormChanger = wb.lexeme.entityChangers.FormChanger;
	var Form = wb.lexeme.datamodel.Form;
	var TermMap = wb.datamodel.TermMap;
	var Term = wb.datamodel.Term;

	QUnit.test( 'New From - makes the expected API call', function ( assert ) {
		var postWithToken = sinon.spy( function () {
			return $.Deferred().resolve( {} ).promise();
		} );
		var api = {
			postWithToken: postWithToken
		};
		var revisionStore = {
			setFormRevision: function () {}
		};

		var lexemeId = 'L11';
		var changer = new FormChanger( api, revisionStore, lexemeId );
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var form = new Form( null, representations, [ 'Q1', 'Q2' ] );

		changer.save( form );

		var callArguments = postWithToken.args[ 0 ];
		var gotTokenType = callArguments[ 0 ];
		var gotParameters = callArguments[ 1 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.equal( gotTokenType, 'csrf', 'Token type' );
		assert.equal( gotParameters.action, 'wbladdform', 'Add form API action' );
		assert.equal( gotParameters.errorformat, 'plaintext', 'Plain text error format' );
		assert.equal( gotParameters.bot, 1, 'BOT flag' );
		assert.equal( gotParameters.lexemeId, lexemeId, 'lexemeId parameter' );
		assert.deepEqual(
			gotData.representations,
			[ { language: 'en', representation: 'test representation' } ],
			'Representation list'
		);
		assert.deepEqual(
			gotData.grammaticalFeatures,
			[ 'Q1', 'Q2' ],
			'Grammatical feature set'
		);
	} );

	QUnit.test( 'New form - save - returns deserialized Form from API result', function ( assert ) {
		var done = assert.async();

		var api = {
			postWithToken: function () {
				return $.Deferred().resolve( {
					form: {
						id: 'L1-F100',
						representations: {
							en: {
								language: 'en',
								value: 'some representation'
							}
						},
						grammaticalFeatures: [ 'Q1', 'Q2' ]
					}
				} ).promise();
			}
		};
		var revisionStore = {
			setFormRevision: function () {}
		};

		var changer = new FormChanger( api, revisionStore, 'L1' );

		var form = new Form( null, null, [] );

		changer.save( form ).then( function ( form ) {
			assert.equal( form.getId(), 'L1-F100', 'Saved Form ID' );
			assert.equal(
				form.getRepresentations().getItemByKey( 'en' ).getText(),
				'some representation',
				'Saved representation'
			);
			assert.deepEqual(
				form.getGrammaticalFeatures(),
				[ 'Q1', 'Q2' ],
				'Saved grammatical features'
			);
			done();
		} ).catch( done );
	} );

	QUnit.test( 'New form - save - sets the base revision to the one from API result', function ( assert ) {
		var done = assert.async();

		var api = {
			postWithToken: function () {
				return $.Deferred().resolve( {
					form: { id: 'L1-F100' },
					lastrevid: 303
				} ).promise();
			}
		};
		var revisionStore = {
			formBaseRevisions: {
			},
			getFormRevision: function ( formId ) {
				return this.formBaseRevisions[ formId ];
			},
			setFormRevision: function ( revision, formId ) {
				this.formBaseRevisions[ formId ] = revision;

			}
		};

		var changer = new FormChanger( api, revisionStore, 'L1' );

		var form = new Form( null, null, [] );

		changer.save( form ).then( function () {
			assert.equal( revisionStore.getFormRevision( 'L1-F100' ), 303 );
			done();
		} ).catch( done );
	} );

	QUnit.test(
		'New form - save fails with errors - converts errors to single RepoApiError',
		function ( assert ) {
			var done = assert.async();

			var api = {
				postWithToken: function () {
					return $.Deferred().reject(
						'some-generic-error-code',
						{
							errors: [
								createError( 'error-code-1', 'Some text 1' ),
								createError( 'error-code-1', 'Some text 2' )
							],
							'*': 'Some info'
						}
					).promise();
				}
			};

			var changer = new FormChanger( api, {}, 'L1' );

			var form = new Form( null, null, [] );

			changer.save( form ).catch( function ( error ) {
				assert.ok(
					error instanceof wb.api.RepoApiError,
					'Error is instance of RepoApiError'
				);
				assert.ok(
					error.detailedMessage.indexOf( 'Some text 1' ) > -1,
					'Detailed message contains text of the first error'
				);
				assert.ok(
					error.detailedMessage.indexOf( 'Some text 2' ) > -1,
					'Detailed message contains text of the second error'
				);
				done();
			} );

			function createError( code, text ) {
				return {
					code: code,
					data: {},
					module: 'wbladdform',
					'*': text
				};
			}
		} );

	QUnit.test( 'Existing Form data changed - makes the expected API call', function ( assert ) {
		var postWithToken = sinon.spy( function () {
			return $.Deferred().resolve( {} ).promise();
		} );
		var api = {
			postWithToken: postWithToken
		};

		var formId = 'L11-F2';
		var changer = new FormChanger( api, {}, 'L11' );
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var form = new Form( formId, representations, [ 'Q1', 'Q2' ] );

		changer.save( form );

		var callArguments = postWithToken.args[ 0 ];
		var gotTokenType = callArguments[ 0 ];
		var gotParameters = callArguments[ 1 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.equal( gotTokenType, 'csrf', 'Token type' );
		assert.equal( gotParameters.action, 'wbleditformelements', 'Edit form elements API action' );
		assert.equal( gotParameters.errorformat, 'plaintext', 'Plain text error format' );
		assert.equal( gotParameters.bot, 0, 'BOT flag' );
		assert.equal( gotParameters.formId, formId, 'formId parameter' );
		assert.deepEqual(
			gotData.representations,
			[ { language: 'en', representation: 'test representation' } ],
			'Representation list'
		);
		assert.deepEqual(
			gotData.grammaticalFeatures,
			[ 'Q1', 'Q2' ],
			'Grammatical feature set'
		);
	} );

	QUnit.test( 'Existing Form data changed - save - returns deserialized Form from API result', function ( assert ) {
		var done = assert.async();

		var formId = 'L1-F100';
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var grammaticalFeatures = [ 'Q1', 'Q2' ];

		var api = {
			postWithToken: function () {
				return $.Deferred().resolve( {
					form: {
						id: formId,
						representations: {
							en: {
								language: 'en',
								value: 'test representation'
							}
						},
						grammaticalFeatures: grammaticalFeatures
					}
				} ).promise();
			}
		};

		var form = new Form( formId, representations, grammaticalFeatures );

		var changer = new FormChanger( api, {}, 'L1' );

		changer.save( form ).then( function ( form ) {
			assert.equal( form.getId(), 'L1-F100', 'Saved Form ID' );
			assert.equal(
				form.getRepresentations().getItemByKey( 'en' ).getText(),
				'test representation',
				'Saved representation'
			);
			assert.deepEqual(
				form.getGrammaticalFeatures(),
				[ 'Q1', 'Q2' ],
				'Saved grammatical features'
			);
			done();
		} ).catch( done );
	} );

	QUnit.test(
		'Existing Form data changed - save fails with errors - converts errors to single RepoApiError',
		function ( assert ) {
			var done = assert.async();

			var api = {
				postWithToken: function () {
					return $.Deferred().reject(
						'some-generic-error-code',
						{
							errors: [
								createError( 'error-code-1', 'Some text 1' ),
								createError( 'error-code-1', 'Some text 2' )
							],
							'*': 'Some info'
						}
					).promise();
				}
			};

			var changer = new FormChanger( api, {}, 'L1' );

			var form = new Form( 'L1-F1', null, [] );

			changer.save( form ).catch( function ( error ) {
				assert.ok(
					error instanceof wb.api.RepoApiError,
					'Error is instance of RepoApiError'
				);
				assert.ok(
					error.detailedMessage.indexOf( 'Some text 1' ) > -1,
					'Detailed message contains text of the first error'
				);
				assert.ok(
					error.detailedMessage.indexOf( 'Some text 2' ) > -1,
					'Detailed message contains text of the second error'
				);
				done();
			} );

			function createError( code, text ) {
				return {
					code: code,
					data: {},
					module: 'wbleditformelements',
					'*': text
				};
			}
		} );

}( jQuery, wikibase, QUnit, sinon ) );
