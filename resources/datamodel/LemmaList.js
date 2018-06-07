( function () {
	'use strict';

	/**
	 * @class wikibase.lexeme.datamodel.LemmaList
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

	module.exports = LemmaList;
} )();
