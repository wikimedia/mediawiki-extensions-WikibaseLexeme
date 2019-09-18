/**
 * @license GPL-2.0-or-later
 */
( function () {
	'use strict';

	QUnit.module( 'wikibase.lexeme.entityChangers.LexemeRevisionStore' );

	var RevisionStore = require( '../../../resources/entityChangers/LexemeRevisionStore.js' );

	QUnit.test( 'getClaimRevision: given a GUID of a lexeme returns correct claimRevision', function ( assert ) {
		var mockBaseStore = {
				getClaimRevision: sinon.stub().withArgs( 'L123$some-GUID' ).returns( 234 )
			},
			store = new RevisionStore( mockBaseStore );

		assert.equal( store.getClaimRevision( 'L123$some-GUID' ), 234 );
	} );

	QUnit.test( 'getClaimRevision: given a known GUID of a form returns form claimRevision', function ( assert ) {
		var store = new RevisionStore( null );

		store.setClaimRevision( 321, 'L123-F2$some-GUID' );

		assert.equal( store.getClaimRevision( 'L123-F2$some-GUID' ), 321 );
	} );

	QUnit.test( 'getClaimRevision: given an unknown GUID of a form returns form revision', function ( assert ) {
		var store = new RevisionStore( null );

		store.setFormRevision( 23, 'L123-F2' );

		assert.equal( store.getClaimRevision( 'L123-F2$some-GUID' ), 23 );
	} );

	QUnit.test( 'getClaimRevision: given a known GUID of a sense returns sense claimRevision', function ( assert ) {
		var store = new RevisionStore( null );

		store.setClaimRevision( 321, 'L123-S2$some-GUID' );

		assert.equal( store.getClaimRevision( 'L123-S2$some-GUID' ), 321 );
	} );

	QUnit.test( 'getClaimRevision: given an unknown GUID of a sense returns sense revision', function ( assert ) {
		var store = new RevisionStore( null );

		store.setSenseRevision( 23, 'L123-S2' );

		assert.equal( store.getClaimRevision( 'L123-S2$some-GUID' ), 23 );
	} );

	QUnit.test( 'setClaimRevision: given a GUID of a lexeme, sets claimRevision in base store', function ( assert ) {
		var setClaimRevSpy = sinon.spy(),
			store = new RevisionStore(
				{ setClaimRevision: setClaimRevSpy }
			);

		store.setClaimRevision( 42, 'L789$some-GUID' );

		assert.ok( setClaimRevSpy.calledWith( 42, 'L789$some-GUID' ) );
	} );

	QUnit.test( 'setClaimRevision: given a GUID of a form sets claimRevision for form', function ( assert ) {
		var store = new RevisionStore( null );

		store.setClaimRevision( 666, 'L3-F1$some-GUID' );

		assert.equal( store.getClaimRevision( 'L3-F1$some-GUID' ), 666 );
	} );

	QUnit.test( 'setClaimRevision: given a GUID of a sense sets claimRevision for sense', function ( assert ) {
		var store = new RevisionStore( null );

		store.setClaimRevision( 666, 'L3-S1$some-GUID' );

		assert.equal( store.getClaimRevision( 'L3-S1$some-GUID' ), 666 );
	} );

	QUnit.test( 'getBaseRevision returns the base revision', function ( assert ) {
		var store = new RevisionStore( {
			getBaseRevision: sinon.stub().returns( 777 )
		} );

		assert.equal( store.getBaseRevision(), 777 );
	} );

	QUnit.test( 'setFormRevision sets the revision for a form id', function ( assert ) {
		var store = new RevisionStore( null );
		store.setFormRevision( 1234, 'L1-F1' );
		assert.equal( store.getFormRevision( 'L1-F1' ), 1234 );
	} );

	QUnit.test( 'getFormRevision gets the revision of a form', function ( assert ) {
		var store = new RevisionStore( null );
		store.setFormRevision( 4321, 'L1-F1' );
		assert.equal( store.getFormRevision( 'L1-F1' ), 4321 );
	} );

	QUnit.test( 'setSenseRevision sets the revision for a sense id', function ( assert ) {
		var store = new RevisionStore( null );
		store.setSenseRevision( 1234, 'L1-S1' );
		assert.equal( store.getSenseRevision( 'L1-S1' ), 1234 );
	} );

	QUnit.test( 'getSenseRevision gets the revision of a sense', function ( assert ) {
		var store = new RevisionStore( null );
		store.setSenseRevision( 4321, 'L1-S1' );
		assert.equal( store.getSenseRevision( 'L1-S1' ), 4321 );
	} );

}() );
