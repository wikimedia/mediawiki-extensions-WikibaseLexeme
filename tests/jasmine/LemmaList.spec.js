/**
 * @license GPL-2.0-or-later
 */
describe( 'LemmaList', function () {
	var expect = require( 'unexpected' ).clone(),
		Lemma = require( './../../resources/datamodel/Lemma.js' ),
		LemmaList = require( './../../resources/datatransfer/LemmaList.js' );

	it( 'getLemmas', function () {
		var lemmas = [
			new Lemma( 'en-ca', 'color' ),
			new Lemma( 'en-gb', 'colour' )
		];

		expect( ( new LemmaList( lemmas ) ).getLemmas(), 'to equal', lemmas );
	} );

	describe( 'copy', function () {
		it( 'creates an identical LemmaList', function () {
			var list = new LemmaList( [
				new Lemma( 'en-ca', 'color' ),
				new Lemma( 'en-gb', 'colour' )
			] );
			expect( list, 'not to be', list.copy() );
			expect( list.getLemmas(), 'to equal', list.copy().getLemmas() );
		} );

		it( 'clones Lemmas', function () {
			var list = new LemmaList( [
				new Lemma( 'en-ca', 'color' ),
				new Lemma( 'en-gb', 'colour' )
			] );
			expect( list.getLemmas()[ 0 ], 'not to be', list.copy().getLemmas()[ 0 ] );
		} );
	} );

	it( 'add', function () {
		var list = new LemmaList( [] ),
			lemma = new Lemma( 'en', 'cool' );

		list.add( lemma );

		expect( list.getLemmas(), 'to contain', lemma );
	} );

	it( 'remove', function () {
		var lemma = new Lemma( 'en', 'potato' ),
			list = new LemmaList( [ lemma ] );

		list.remove( lemma );

		expect( list.getLemmas(), 'not to contain', lemma );
	} );

	it( 'length', function () {
		var list = new LemmaList( [] );

		expect( list.length(), 'to be', 0 );

		list.add( new Lemma( 'en', 'cool' ) );

		expect( list.length(), 'to be', 1 );
	} );

	describe( 'equals', function () {
		it( 'returns false for objects that are not of type LemmaList', function () {
			expect( ( new LemmaList( [] ) ).equals( null ), 'to be false' );
		} );

		it( 'returns false for LemmaList of different length', function () {
			var list = new LemmaList( [ new Lemma( 'en', 'foo' ) ] ),
				other = new LemmaList( [] );
			expect( list.equals( other ), 'to be false' );
		} );

		it( 'returns false for LemmaList with different lemmas', function () {
			var list = new LemmaList( [ new Lemma( 'en', 'foo' ) ] ),
				other = new LemmaList( [ new Lemma( 'en', 'bar' ) ] );
			expect( list.equals( other ), 'to be false' );
		} );

		it( 'returns true for LemmaList with same lemmas', function () {
			var list = new LemmaList( [ new Lemma( 'en', 'foo' ) ] ),
				other = new LemmaList( [ new Lemma( 'en', 'foo' ) ] );
			expect( list.equals( other ), 'to be true' );
		} );

		it( 'ignores empty lemmas', function () {
			var list = new LemmaList( [] ),
				other = list.copy();

			list.add( new Lemma( '', '' ) );
			expect( list.equals( other ), 'to be true' );
		} );

	} );

} );
