( function () {
	'use strict';

	/**
	 * @class wikibase.lexeme.datatransfer.LemmaList
	 *
	 * @param {Lemma[]} lemmas
	 */
	function LemmaList( lemmas ) {
		this.lemmas = lemmas;
	}

	/**
	 * @returns {Lemma[]}
	 */
	LemmaList.prototype.getLemmas = function () {
		return this.lemmas;
	};

	/**
	 * @returns {LemmaList}
	 */
	LemmaList.prototype.copy = function () {
		return new LemmaList( this.lemmas.map( function ( l ) {
			return l.copy();
		} ) );
	};

	/**
	 * @param {Lemma} lemma
	 */
	LemmaList.prototype.add = function ( lemma ) {
		this.lemmas.push( lemma );
	};

	/**
	 * @param {Lemma} lemma
	 */
	LemmaList.prototype.remove = function ( lemma ) {
		var index = this.lemmas.indexOf( lemma );
		this.lemmas.splice( index, 1 );
	};

	/**
	 * @returns {Number}
	 */
	LemmaList.prototype.length = function () {
		return this.lemmas.length;
	};

	/**
	 * Compares two LemmaList objects ignoring empty Lemmas
	 *
	 * @param {*} other
	 * @returns {boolean}
	 */
	LemmaList.prototype.equals = function ( other ) {
		if ( !( other instanceof LemmaList ) ) {
			return false;
		}

		var nonEmptyLemmas = getNonEmptyLemmas( this.lemmas );

		return other.length() === nonEmptyLemmas.length
			&& hasSameLemmas( nonEmptyLemmas, other.getLemmas() );
	};

	function getNonEmptyLemmas( lemmas ) {
		return lemmas.filter( function ( l ) {
			return l.language !== '' && l.value !== '';
		} );
	}

	/**
	 * ownLemmas and other are assumed to have the same length
	 *
	 * @param {Lemma[]} ownLemmas
	 * @param {Lemma[]} other
	 *
	 * @returns {boolean}
	 */
	function hasSameLemmas( ownLemmas, other ) {
		for ( var i = 0; i < ownLemmas.length; i++ ) {
			if (
				ownLemmas[ i ].language !== other[ i ].language
				|| ownLemmas[ i ].value !== other[ i ].value
			) {
				return false;
			}
		}

		return true;
	}

	module.exports = LemmaList;
} )();
