module.exports = ( function () {
	'use strict';

	/**
	 * Vue mixin to provide detection of Term-like objects with repeated languages in an array
	 *
	 * @param {string} watchedProperty The property containing the term list on your component
	 *
	 * @return {Object} Vue component object
	 */
	return function ( watchedProperty ) {
		var definition = {
			compatConfig: { MODE: 3 },
			data: function () {
				return {
					redundantLanguages: []
				};
			},
			watch: {
			},
			computed: {
				hasRedundantLanguage: function () {
					return this.redundantLanguages.length > 0;
				}
			},
			methods: {
				/**
				 * @param {string} language The language you want to check for redundancy
				 * @return {boolean}
				 */
				isRedundantLanguage: function ( language ) {
					return this.redundantLanguages.indexOf( language ) > -1;
				}
			}
		};

		// adds the watch only for the property with the given name
		definition.watch[ watchedProperty ] = {
			handler: detectRedundantLanguages,
			deep: true,
			immediate: true
		};

		return definition;
	};

	function detectRedundantLanguages( termList ) {
		var redundantLanguages = [],
			languages = [],
			language;

		for ( var currentIndex = 0; currentIndex < termList.length; currentIndex++ ) {
			language = termList[ currentIndex ].language;

			if ( language === '' ) {
				continue; // blank forms are not considered conflicting
			}

			if ( languages.indexOf( language ) > -1 ) {
				redundantLanguages.push( language );
			}

			languages.push( language );
		}

		this.redundantLanguages = redundantLanguages;
		this.$emit( 'hasRedundantLanguage', this.hasRedundantLanguage );
	}

}() );
