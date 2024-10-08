/**
 * @license GPL-2.0-or-later
 */

( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.entityChangers.FormChanger' );

	var FormChanger = require( '../../../resources/entityChangers/FormChanger.js' );
	var Form = wb.lexeme.datamodel.Form;
	var datamodel = require( 'wikibase.datamodel' );
	var TermMap = datamodel.TermMap;
	var Term = datamodel.Term;
	var revisionStore = {
		setFormRevision: function () {},
		getBaseRevision: function () {
			return 123;
		}
	};

	QUnit.test( 'New Form - makes the expected API call', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				form: {}
			} ).promise();
		} );
		var api = {
			post: post,
			normalizeMultiValue: function ( stuff ) {
				return stuff;
			}
		};

		var tags = [ 'asdf' ];
		var lexemeId = 'L11';
		var changer = new FormChanger( api, revisionStore, lexemeId, {}, tags );
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var form = new Form( null, representations, [ 'Q1', 'Q2' ] );

		changer.save( form );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbladdform', 'Add form API action' );
		assert.strictEqual( gotParameters.errorformat, 'plaintext', 'Plain text error format' );
		assert.strictEqual( gotParameters.bot, 0, 'BOT flag' );
		assert.strictEqual( gotParameters.baserevid, undefined, 'Base revision Id should not be sent' );
		assert.strictEqual( gotParameters.lexemeId, lexemeId, 'lexemeId parameter' );
		assert.strictEqual( gotParameters.tags, tags, 'Tags should be set' );
		assert.deepEqual(
			gotData.representations,
			{ en: { language: 'en', value: 'test representation' } },
			'Representation list'
		);
		assert.deepEqual(
			gotData.grammaticalFeatures,
			[ 'Q1', 'Q2' ],
			'Grammatical feature set'
		);
	} );

	QUnit.test( 'New form - save - returns deserialized Form from API result', function ( assert ) {
		var api = {
			post: function () {
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

		var changer = new FormChanger( api, revisionStore, 'L1', {} );

		var form = new Form( null, null, [] );

		return changer.save( form ).then( function ( valueChangeResult ) {
			var saveForm = valueChangeResult.getSavedValue();
			assert.strictEqual( saveForm.getId(), 'L1-F100', 'Saved Form ID' );
			assert.strictEqual(
				saveForm.getRepresentations().getItemByKey( 'en' ).getText(),
				'some representation',
				'Saved representation'
			);
			assert.deepEqual(
				saveForm.getGrammaticalFeatures(),
				[ 'Q1', 'Q2' ],
				'Saved grammatical features'
			);
		} );
	} );

	QUnit.test( 'New form - save - handles tempuser redirect if present', function ( assert ) {
		var targetUrl = 'https://wiki.example';

		var api = {
			post: function () {
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
					},
					tempusercreated: 'tempuser',
					tempuserredirect: targetUrl
				} ).promise();
			}
		};

		var changer = new FormChanger( api, revisionStore, 'L1', {} );

		var form = new Form( null, null, [] );

		return changer.save( form ).then( function ( valueChangeResult ) {
			var tempUserWatcher = valueChangeResult.getTempUserWatcher();
			assert.strictEqual( targetUrl, tempUserWatcher.getRedirectUrl() );
		} );
	} );

	QUnit.test( 'New form - save - sets the base revision to the one from API result', function ( assert ) {
		var api = {
			post: function () {
				return $.Deferred().resolve( {
					form: { id: 'L1-F100' },
					lastrevid: 303
				} ).promise();
			}
		};
		var revisionStore2 = {
			formBaseRevisions: {
			},
			getFormRevision: function ( formId ) {
				return this.formBaseRevisions[ formId ];
			},
			setFormRevision: function ( revision, formId ) {
				this.formBaseRevisions[ formId ] = revision;

			},
			getBaseRevision: function () {
				return 123;
			}
		};

		var changer = new FormChanger( api, revisionStore2, 'L1', {} );

		var form = new Form( null, null, [] );

		return changer.save( form ).then( function () {
			assert.strictEqual( revisionStore2.getFormRevision( 'L1-F100' ), 303 );
		} );
	} );

	QUnit.test(
		'New form - save fails with errors - converts errors to single RepoApiError',
		async function ( assert ) {
			var api = {
				post: function () {
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

			var changer = new FormChanger( api, revisionStore, 'L1', {} );

			var form = new Form( null, null, [] );

			try {
				await changer.save( form );
				assert.true( false, 'expected uncaught error' );
			} catch ( error ) {
				assert.true(
					error instanceof wb.api.RepoApiError,
					'Error is instance of RepoApiError'
				);
				assert.true(
					error.detailedMessage.includes( 'Some text 1' ),
					'Detailed message contains text of the first error'
				);
				assert.true(
					error.detailedMessage.includes( 'Some text 2' ),
					'Detailed message contains text of the second error'
				);
			}

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
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				form: {}
			} ).promise();
		} );
		var api = {
			post: post
		};

		var formId = 'L11-F2';
		var oldFormData = {
			representations: {
				en: { language: 'en', value: 'old representation' }
			},
			grammaticalFeatures: [ 'Q1' ]
		};

		var changer = new FormChanger( api, revisionStore, 'L11', oldFormData );
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var form = new Form( formId, representations, [ 'Q1', 'Q2' ] );

		changer.save( form );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbleditformelements', 'Edit form elements API action' );
		assert.strictEqual( gotParameters.errorformat, 'plaintext', 'Plain text error format' );
		assert.strictEqual( gotParameters.bot, 0, 'BOT flag' );
		assert.strictEqual( gotParameters.baserevid, 123, 'Base revision Id' );
		assert.strictEqual( gotParameters.formId, formId, 'formId parameter' );
		assert.deepEqual(
			gotData.representations,
			{ en: { language: 'en', value: 'test representation' } },
			'Representation list'
		);
		assert.deepEqual(
			gotData.grammaticalFeatures,
			[ 'Q1', 'Q2' ],
			'Grammatical feature set'
		);
	} );

	QUnit.test( 'Representation added - only new representation passed to API', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				form: {}
			} ).promise();
		} );
		var api = {
			post: post
		};

		var formId = 'L11-F2';
		var oldFormData = {
			representations: {
				en: { language: 'en', value: 'test representation' }
			},
			grammaticalFeatures: [ 'Q1' ]
		};

		var changer = new FormChanger( api, revisionStore, 'L11', oldFormData );
		var representations = new TermMap( {
			en: new Term( 'en', 'test representation' ),
			'en-gb': new Term( 'en-gb', 'test representation gb' )
		} );
		var form = new Form( formId, representations, [ 'Q1' ] );

		changer.save( form );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbleditformelements', 'Edit form elements API action' );
		assert.deepEqual(
			gotData.representations,
			{ 'en-gb': { language: 'en-gb', value: 'test representation gb' } },
			'Representation list'
		);
	} );

	QUnit.test( 'One of many representations changed - only changed representation passed to API', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				form: {}
			} ).promise();
		} );
		var api = {
			post: post
		};

		var formId = 'L11-F2';
		var oldFormData = {
			representations: {
				en: { language: 'en', value: 'old representation' },
				'en-gb': { language: 'en-gb', value: 'old representation gb' }
			},
			grammaticalFeatures: [ 'Q1' ]
		};

		var changer = new FormChanger( api, revisionStore, 'L11', oldFormData );
		var representations = new TermMap( {
			en: new Term( 'en', 'new representation' ),
			'en-gb': new Term( 'en-gb', 'old representation gb' )
		} );
		var form = new Form( formId, representations, [ 'Q1' ] );

		changer.save( form );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbleditformelements', 'Edit form elements API action' );
		assert.deepEqual(
			gotData.representations,
			{ en: { language: 'en', value: 'new representation' } },
			'Representation list'
		);
	} );

	QUnit.test( 'Representation removed - remove request passed to API', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				form: {}
			} ).promise();
		} );
		var api = {
			post: post
		};

		var formId = 'L11-F2';
		var oldFormData = {
			representations: {
				en: { language: 'en', value: 'test representation' },
				'en-gb': { language: 'en-gb', value: 'test representation gb' }
			},
			grammaticalFeatures: [ 'Q1' ]
		};

		var changer = new FormChanger( api, revisionStore, 'L11', oldFormData );
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var form = new Form( formId, representations, [ 'Q1' ] );

		changer.save( form );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbleditformelements', 'Edit form elements API action' );
		assert.deepEqual(
			gotData.representations,
			{ 'en-gb': { language: 'en-gb', remove: '' } },
			'Remove representation'
		);
	} );

	QUnit.test( 'Representation removed - temp user redirect handled if present', function ( assert ) {
		var targetUrl = 'https://wiki.example';
		var api = {
			post: function () {
				return $.Deferred().resolve( {
					form: {},
					tempusercreated: 'tempuser',
					tempuserredirect: targetUrl
				} ).promise();
			}
		};

		var formId = 'L11-F2';
		var oldFormData = {
			representations: {
				en: { language: 'en', value: 'test representation' },
				'en-gb': { language: 'en-gb', value: 'test representation gb' }
			},
			grammaticalFeatures: [ 'Q1' ]
		};
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );

		var form = new Form( formId, representations, [ 'Q1' ] );

		var changer = new FormChanger( api, revisionStore, 'L1', oldFormData );

		return changer.remove( form ).then( function ( valueChangeResult ) {
			var tempUserWatcher = valueChangeResult.getTempUserWatcher();
			assert.strictEqual( targetUrl, tempUserWatcher.getRedirectUrl() );
		} );
	} );

	QUnit.test( 'Existing Form data changed - save - returns deserialized Form from API result', function ( assert ) {
		var formId = 'L1-F100';
		var oldFormData = {
			representations: {
				en: { language: 'en', value: 'old representation' }
			},
			grammaticalFeatures: [ 'Q1' ]
		};
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var grammaticalFeatures = [ 'Q1', 'Q2' ];

		var api = {
			post: function () {
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

		var changer = new FormChanger( api, revisionStore, 'L1', oldFormData );

		return changer.save( form ).then( function ( valueChangeResult ) {
			var saveForm = valueChangeResult.getSavedValue();
			assert.strictEqual( saveForm.getId(), 'L1-F100', 'Saved Form ID' );
			assert.strictEqual(
				saveForm.getRepresentations().getItemByKey( 'en' ).getText(),
				'test representation',
				'Saved representation'
			);
			assert.deepEqual(
				saveForm.getGrammaticalFeatures(),
				[ 'Q1', 'Q2' ],
				'Saved grammatical features'
			);
		} );
	} );

	QUnit.test(
		'Existing Form data changed - save fails with errors - converts errors to single RepoApiError',
		async function ( assert ) {
			var api = {
				post: function () {
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

			var changer = new FormChanger( api, revisionStore, 'L1', {} );

			var form = new Form( 'L1-F1', null, [] );

			try {
				await changer.save( form );
				assert.true( false, 'expected uncaught error' );
			} catch ( error ) {
				assert.true(
					error instanceof wb.api.RepoApiError,
					'Error is instance of RepoApiError'
				);
				assert.true(
					error.detailedMessage.includes( 'Some text 1' ),
					'Detailed message contains text of the first error'
				);
				assert.true(
					error.detailedMessage.includes( 'Some text 2' ),
					'Detailed message contains text of the second error'
				);

			}

			function createError( code, text ) {
				return {
					code: code,
					data: {},
					module: 'wbleditformelements',
					'*': text
				};
			}
		} );

	QUnit.test( 'Existing Form removed - makes the expected API call', function ( assert ) {
		var api = {
			post: sinon.stub().returns( $.Deferred().resolve( {} ) )
		};

		var formId = 'L11-F2';
		var changer = new FormChanger( api, revisionStore, 'L11', {} );
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var form = new Form( formId, representations, [ 'Q1', 'Q2' ] );

		changer.remove( form );

		assert.true( api.post.calledOnce, 'API gets called once' );

		var callArguments = api.post.firstCall.args;
		var gotParameters = callArguments[ 0 ];

		assert.strictEqual( gotParameters.action, 'wblremoveform', 'Picks right API action' );
		assert.strictEqual( gotParameters.id, formId, 'Sends form id parameter' );
		assert.strictEqual( gotParameters.errorformat, 'plaintext', 'Requests plain text error format' );
		assert.strictEqual( gotParameters.baserevid, 123, 'Base revision Id' );
		assert.strictEqual( gotParameters.bot, 0, 'Disables bot flag' );
	} );

	QUnit.test( 'Existing Form removal fails - formats and passes API errors', async function ( assert ) {
		var api = {
			post: sinon.stub().returns(
				$.Deferred().reject( 'irrelevant', { errors: [ { code: 'bad', '*': 'foo' } ] } )
			)
		};

		var changer = new FormChanger( api, revisionStore, 'L11', {} );
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var form = new Form( 'L11-F300', representations, [ 'Q1', 'Q2' ] );

		try {
			await changer.remove( form );
			assert.true( false, 'should throw apiEror' );
		} catch ( apiError ) {
			assert.true( apiError instanceof wb.api.RepoApiError, 'Is custom API error' );
			assert.strictEqual( apiError.code, 'bad', 'Code from API gets set' );
			assert.strictEqual( apiError.detailedMessage, '<li>foo</li>', 'Message from API gets set and decorated' );
			assert.strictEqual( apiError.action, 'remove', 'Action that failed gets set' );

		}
	} );

}( wikibase ) );
