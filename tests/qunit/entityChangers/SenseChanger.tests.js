/**
 * @license GPL-2.0-or-later
 */

( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.entityChangers.SenseChanger' );

	var SenseChanger = require( '../../../resources/entityChangers/SenseChanger.js' );
	var Sense = wb.lexeme.datamodel.Sense;
	var datamodel = require( 'wikibase.datamodel' );
	var TermMap = datamodel.TermMap;
	var Term = datamodel.Term;
	var revisionStore = {
		setSenseRevision: function () {},
		getBaseRevision: function () {
			return 123;
		}
	};

	QUnit.test( 'New Sense - makes the expected API call', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				sense: {}
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
		var changer = new SenseChanger( api, revisionStore, lexemeId, {}, tags );
		var glosses = new TermMap( { en: new Term( 'en', 'test gloss' ) } );
		var sense = new Sense( null, glosses );

		changer.save( sense );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbladdsense', 'Add sense API action' );
		assert.strictEqual( gotParameters.errorformat, 'plaintext', 'Plain text error format' );
		assert.strictEqual( gotParameters.bot, 0, 'BOT flag' );
		assert.strictEqual( gotParameters.baserevid, undefined, 'Base revision Id should not be sent' );
		assert.strictEqual( gotParameters.lexemeId, lexemeId, 'lexemeId parameter' );
		assert.strictEqual( gotParameters.tags, tags, 'Tags should be set' );
		assert.deepEqual(
			gotData.glosses,
			{ en: { language: 'en', value: 'test gloss' } },
			'Glosses list'
		);
	} );

	QUnit.test( 'New sense - save - returns deserialized Sense from API result', function ( assert ) {
		var api = {
			post: function () {
				return $.Deferred().resolve( {
					sense: {
						id: 'L1-S100',
						glosses: {
							en: {
								language: 'en',
								value: 'some gloss'
							}
						}
					}
				} ).promise();
			}
		};

		var changer = new SenseChanger( api, revisionStore, 'L1', {} );

		var sense = new Sense( null, null );

		return changer.save( sense ).then( function ( valueChangeResult ) {
			assert.strictEqual( valueChangeResult.getSavedValue().getId(), 'L1-S100', 'Saved Sense ID' );
			assert.strictEqual(
				valueChangeResult.getSavedValue().getGlosses().getItemByKey( 'en' ).getText(),
				'some gloss',
				'Saved gloss'
			);
		} );
	} );

	QUnit.test( 'New sense - save - handles redirecturl if present in API response', function ( assert ) {
		var targetUrl = 'https://wiki.example/';

		var api = {
			post: function () {
				return $.Deferred().resolve( {
					sense: {
						id: 'L1-S100',
						glosses: {
							en: {
								language: 'en',
								value: 'some gloss'
							}
						}
					},
					tempusercreated: 'tempuser',
					tempuserredirect: targetUrl
				} ).promise();
			}
		};

		var changer = new SenseChanger( api, revisionStore, 'L1', {} );

		var sense = new Sense( null, null );

		return changer.save( sense ).then( function ( valueChangeResult ) {
			assert.strictEqual( targetUrl, valueChangeResult.getTempUserWatcher().getRedirectUrl() );
		} );
	} );

	QUnit.test( 'New sense - save - sets the base revision to the one from API result', function ( assert ) {
		var api = {
			post: function () {
				return $.Deferred().resolve( {
					sense: { id: 'L1-S100' },
					lastrevid: 303
				} ).promise();
			}
		};
		var revisionStore2 = {
			senseBaseRevisions: {
			},
			getSenseRevision: function ( senseId ) {
				return this.senseBaseRevisions[ senseId ];
			},
			setSenseRevision: function ( revision, senseId ) {
				this.senseBaseRevisions[ senseId ] = revision;

			},
			getBaseRevision: function () {
				return 123;
			}
		};

		var changer = new SenseChanger( api, revisionStore2, 'L1', {} );

		var sense = new Sense( null, null );

		return changer.save( sense ).then( function () {
			assert.strictEqual( revisionStore2.getSenseRevision( 'L1-S100' ), 303 );
		} );
	} );

	QUnit.test(
		'New sense - save fails with errors - converts errors to single RepoApiError',
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

			var changer = new SenseChanger( api, revisionStore, 'L1', {} );

			var sense = new Sense( null, null );

			try {
				await changer.save( sense );
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
					module: 'wbladdsense',
					'*': text
				};
			}
		} );

	QUnit.test( 'Existing Sense data changed - makes the expected API call', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				sense: {}
			} ).promise();
		} );

		var api = {
			post: post
		};

		var senseId = 'L11-S2';
		var oldSenseData = {
			glosses: {
				en: { language: 'en', value: 'old gloss' }
			}
		};

		var changer = new SenseChanger( api, revisionStore, 'L11', oldSenseData );
		var glosses = new TermMap( { en: new Term( 'en', 'test gloss' ) } );
		var sense = new Sense( senseId, glosses );

		changer.save( sense );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbleditsenseelements', 'Edit sense elements API action' );
		assert.strictEqual( gotParameters.errorformat, 'plaintext', 'Plain text error format' );
		assert.strictEqual( gotParameters.bot, 0, 'BOT flag' );
		assert.strictEqual( gotParameters.baserevid, 123, 'Base revision Id' );
		assert.strictEqual( gotParameters.senseId, senseId, 'senseId parameter' );
		assert.deepEqual(
			gotData.glosses,
			{ en: { language: 'en', value: 'test gloss' } },
			'Gloss list'
		);
	} );

	QUnit.test( 'Gloss added - only new gloss passed to API', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				sense: {}
			} ).promise();
		} );
		var api = {
			post: post
		};

		var senseId = 'L11-S2';
		var oldSenseData = {
			glosses: {
				en: { language: 'en', value: 'test gloss' }
			}
		};

		var changer = new SenseChanger( api, revisionStore, 'L11', oldSenseData );
		var glosses = new TermMap( {
			en: new Term( 'en', 'test gloss' ),
			'en-gb': new Term( 'en-gb', 'test gloss gb' )
		} );
		var sense = new Sense( senseId, glosses );

		changer.save( sense );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbleditsenseelements', 'Edit sense elements API action' );
		assert.deepEqual(
			gotData.glosses,
			{ 'en-gb': { language: 'en-gb', value: 'test gloss gb' } },
			'Gloss list'
		);
	} );

	QUnit.test( 'One of many glosses changed - only changed gloss passed to API', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				sense: {}
			} ).promise();
		} );
		var api = {
			post: post
		};

		var senseId = 'L11-S2';
		var oldSenseData = {
			glosses: {
				en: { language: 'en', value: 'old gloss' },
				'en-gb': { language: 'en-gb', value: 'old gloss gb' }
			}
		};

		var changer = new SenseChanger( api, revisionStore, 'L11', oldSenseData );
		var glosses = new TermMap( {
			en: new Term( 'en', 'new gloss' ),
			'en-gb': new Term( 'en-gb', 'old gloss gb' )
		} );
		var sense = new Sense( senseId, glosses );

		changer.save( sense );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbleditsenseelements', 'Edit sense elements API action' );
		assert.deepEqual(
			gotData.glosses,
			{ en: { language: 'en', value: 'new gloss' } },
			'Gloss list'
		);
	} );

	QUnit.test( 'Gloss removed - remove request passed to API', function ( assert ) {
		var post = sinon.spy( function () {
			return $.Deferred().resolve( {
				sense: {}
			} ).promise();
		} );
		var api = {
			post: post
		};

		var senseId = 'L11-S2';
		var oldSenseData = {
			glosses: {
				en: { language: 'en', value: 'test gloss' },
				'en-gb': { language: 'en-gb', value: 'test gloss gb' }
			}
		};

		var changer = new SenseChanger( api, revisionStore, 'L11', oldSenseData );
		var glosses = new TermMap( { en: new Term( 'en', 'test gloss' ) } );
		var sense = new Sense( senseId, glosses );

		changer.save( sense );

		var callArguments = post.args[ 0 ];
		var gotParameters = callArguments[ 0 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.strictEqual( gotParameters.action, 'wbleditsenseelements', 'Edit sense elements API action' );
		assert.deepEqual(
			gotData.glosses,
			{ 'en-gb': { language: 'en-gb', remove: '' } },
			'Remove gloss'
		);
	} );

	QUnit.test( 'Sense removed - handles redirecturl if present in API response', function ( assert ) {
		var targetUrl = 'https://wiki.example';
		var api = {
			post: sinon.stub().returns(
				$.Deferred().resolve( {
					sense: {},
					tempusercreated: 'tempuser',
					tempuserredirect: targetUrl
				} )
			)
		};

		var changer = new SenseChanger( api, revisionStore, 'L11', {} );
		var glosses = new TermMap( { en: new Term( 'en', 'test gloss' ) } );
		var sense = new Sense( 'L11-S300', glosses );

		return changer.remove( sense ).then( function ( valueChangeResult ) {
			assert.strictEqual( targetUrl, valueChangeResult.getTempUserWatcher().getRedirectUrl() );
		} );
	} );

	QUnit.test( 'Existing Sense data changed - save - returns deserialized Sense from API result', function ( assert ) {
		var senseId = 'L1-S100';
		var oldSenseData = {
			glosses: {
				en: { language: 'en', value: 'old gloss' }
			}
		};
		var glosses = new TermMap( { en: new Term( 'en', 'test gloss' ) } );

		var api = {
			post: function () {
				return $.Deferred().resolve( {
					sense: {
						id: senseId,
						glosses: {
							en: {
								language: 'en',
								value: 'test gloss'
							}
						}
					}
				} ).promise();
			}
		};

		var sense = new Sense( senseId, glosses );

		var changer = new SenseChanger( api, revisionStore, 'L1', oldSenseData );

		return changer.save( sense ).then( function ( valueChangeResult ) {
			assert.strictEqual( valueChangeResult.getSavedValue().getId(), 'L1-S100', 'Saved Sense ID' );
			assert.strictEqual(
				valueChangeResult.getSavedValue().getGlosses().getItemByKey( 'en' ).getText(),
				'test gloss',
				'Saved gloss'
			);
		} );
	} );

	QUnit.test(
		'Existing Sense data changed - save fails with errors - converts errors to single RepoApiError',
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

			var changer = new SenseChanger( api, revisionStore, 'L1', {} );

			var sense = new Sense( 'L1-S1', null );

			try {
				await changer.save( sense );
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
					module: 'wbleditsenseelements',
					'*': text
				};
			}
		} );

	QUnit.test( 'Existing Sense removed - makes the expected API call', function ( assert ) {
		var api = {
			post: sinon.stub().returns( $.Deferred().resolve( {} ) )
		};

		var senseId = 'L11-S2';
		var changer = new SenseChanger( api, revisionStore, 'L11', {} );
		var glosses = new TermMap( { en: new Term( 'en', 'test gloss' ) } );
		var sense = new Sense( senseId, glosses );

		changer.remove( sense );

		assert.true( api.post.calledOnce, 'API gets called once' );

		var callArguments = api.post.firstCall.args;
		var gotParameters = callArguments[ 0 ];

		assert.strictEqual( gotParameters.action, 'wblremovesense', 'Picks right API action' );
		assert.strictEqual( gotParameters.id, senseId, 'Sends form id parameter' );
		assert.strictEqual( gotParameters.errorformat, 'plaintext', 'Requests plain text error format' );
		assert.strictEqual( gotParameters.bot, 0, 'Disables bot flag' );
		assert.strictEqual( gotParameters.baserevid, 123, 'Base revision Id' );
	} );

	QUnit.test( 'Existing Sense removal fails - formats and passes API errors', async function ( assert ) {
		var api = {
			post: sinon.stub().returns(
				$.Deferred().reject( 'irrelevant', { errors: [ { code: 'bad', '*': 'foo' } ] } )
			)
		};

		var changer = new SenseChanger( api, revisionStore, 'L11', {} );
		var glosses = new TermMap( { en: new Term( 'en', 'test gloss' ) } );
		var sense = new Sense( 'L11-S300', glosses );

		try {
			await changer.remove( sense );
			assert.true( false, 'expected uncaught error' );
		} catch ( apiError ) {
			assert.true( apiError instanceof wb.api.RepoApiError, 'Is custom API error' );
			assert.strictEqual( apiError.code, 'bad', 'Code from API gets set' );
			assert.strictEqual( apiError.detailedMessage, '<li>foo</li>', 'Message from API gets set and decorated' );
			assert.strictEqual( apiError.action, 'remove', 'Action that failed gets set' );

		}
	} );

}( wikibase ) );
