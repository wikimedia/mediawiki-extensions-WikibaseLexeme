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

		assert.equal( 'csrf', gotTokenType, 'Token type' );
		assert.equal( 1, gotParameters.bot, 'BOT flag' );
		assert.equal( lexemeId, gotParameters.lexemeId, 'lexemeId parameter' );
		assert.deepEqual(
			[ { language: 'en', representation: 'test representation' } ],
			gotData.representations,
			'Representation list'
		);
		assert.deepEqual(
			[ 'Q1', 'Q2' ],
			gotData.grammaticalFeatures,
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

}( jQuery, wikibase, QUnit, sinon ) );
