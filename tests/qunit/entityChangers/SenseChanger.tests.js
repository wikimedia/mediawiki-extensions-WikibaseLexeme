/**
 * @license GPL-2.0-or-later
 */
( function ( $, wb, QUnit, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.lexeme.entityChangers.SenseChanger' );

	var SenseChanger = wb.lexeme.entityChangers.SenseChanger;
	var Sense = wb.lexeme.datamodel.Sense;
	var TermMap = wb.datamodel.TermMap;
	var Term = wb.datamodel.Term;

	QUnit.test( 'New Sense - makes the expected API call', function ( assert ) {
		var postWithToken = sinon.spy( function () {
			return $.Deferred().resolve( {} ).promise();
		} );
		var api = {
			postWithToken: postWithToken
		};
		var revisionStore = {
			setSenseRevision: function () {}
		};

		var lexemeId = 'L11';
		var changer = new SenseChanger( api, revisionStore, lexemeId, {} );
		var glosses = new TermMap( { en: new Term( 'en', 'test gloss' ) } );
		var sense = new Sense( null, glosses );

		changer.save( sense );

		var callArguments = postWithToken.args[ 0 ];
		var gotTokenType = callArguments[ 0 ];
		var gotParameters = callArguments[ 1 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.equal( gotTokenType, 'csrf', 'Token type' );
		assert.equal( gotParameters.action, 'wbladdsense', 'Add sense API action' );
		assert.equal( gotParameters.errorformat, 'plaintext', 'Plain text error format' );
		assert.equal( gotParameters.bot, 0, 'BOT flag' );
		assert.equal( gotParameters.lexemeId, lexemeId, 'lexemeId parameter' );
		assert.deepEqual(
			gotData.glosses,
			{ en: { language: 'en', value: 'test gloss' } },
			'Glosses list'
		);
	} );

	QUnit.test( 'New sense - save - returns deserialized Sense from API result', function ( assert ) {
		var done = assert.async();

		var api = {
			postWithToken: function () {
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
		var revisionStore = {
			setSenseRevision: function () {}
		};

		var changer = new SenseChanger( api, revisionStore, 'L1', {} );

		var sense = new Sense( null, null );

		changer.save( sense ).then( function ( sense ) {
			assert.equal( sense.getId(), 'L1-S100', 'Saved Sense ID' );
			assert.equal(
				sense.getglosses().getItemByKey( 'en' ).getText(),
				'some gloss',
				'Saved gloss'
			);
			done();
		} ).catch( done );
	} );

	QUnit.test( 'New sense - save - sets the base revision to the one from API result', function ( assert ) {
		var done = assert.async();

		var api = {
			postWithToken: function () {
				return $.Deferred().resolve( {
					sense: { id: 'L1-S100' },
					lastrevid: 303
				} ).promise();
			}
		};
		var revisionStore = {
			senseBaseRevisions: {
			},
			getSenseRevision: function ( senseId ) {
				return this.senseBaseRevisions[ senseId ];
			},
			setSenseRevision: function ( revision, senseId ) {
				this.senseBaseRevisions[ senseId ] = revision;

			}
		};

		var changer = new SenseChanger( api, revisionStore, 'L1', {} );

		var sense = new Sense( null, null );

		changer.save( sense ).then( function () {
			assert.equal( revisionStore.getSenseRevision( 'L1-S100' ), 303 );
			done();
		} ).catch( done );
	} );

	QUnit.test(
		'New sense - save fails with errors - converts errors to single RepoApiError',
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

			var changer = new SenseChanger( api, {}, 'L1', {} );

			var sense = new Sense( null, null );

			changer.save( sense ).catch( function ( error ) {
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
					module: 'wbladdsense',
					'*': text
				};
			}
		} );

}( jQuery, wikibase, QUnit, sinon ) );
