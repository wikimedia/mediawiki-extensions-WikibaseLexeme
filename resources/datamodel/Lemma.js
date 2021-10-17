( function () {
	'use strict';

	/**
	 * @param {string} label
	 * @param {string} language
	 */
	function Lemma( label, language ) {
		this.value = label;
		this.language = language;
	}

	Lemma.prototype.copy = function () {
		return new Lemma( this.value, this.language );
	};

	module.exports = Lemma;
}() );
