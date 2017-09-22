/**
 * @license GPL-2.0+
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

		var lexemeId = 'L11';
		var changer = new FormChanger( api, lexemeId );
		var representations = new TermMap( { en: new Term( 'en', 'test representation' ) } );
		var form = new Form( null, representations, [ 'Q1', 'Q2' ] );

		changer.save( form );

		var callArguments = postWithToken.args[ 0 ];
		var gotTokenType = callArguments[ 0 ];
		var gotParameters = callArguments[ 1 ];
		var gotData = JSON.parse( gotParameters.data );

		assert.equal( gotTokenType, 'csrf', 'Token type' );
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
						id: 'F100',
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

		var changer = new FormChanger( api, 'L1' );

		var form = new Form( null, null, [] );

		changer.save( form ).then( function ( form ) {
			assert.equal( form.getId(), 'F100', 'Saved Form ID' );
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

			var changer = new FormChanger( api, 'L1' );

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
					module: 'wblexemeaddform',
					'*': text
				};
			}
		} );

}( jQuery, wikibase, QUnit, sinon ) );
