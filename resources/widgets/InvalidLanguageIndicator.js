module.exports = ( function () {
	'use strict';

	/**
	 * Vue mixin to provide detection of Term-like objects with invalid languages in an array
	 *
	 * @param {string} watchedProperty The property containing the term list on your component
	 * @param {string[]} validLanguages
	 *
	 * @return {Object} Vue component object
	 */
	return function ( watchedProperty, validLanguages ) {
		var definition = {
			compatConfig: { MODE: 3 },
			data: function () {
				return {
					InvalidLanguages: []
				};
			},
			watch: {},
			computed: {
				hasInvalidLanguage: function () {
					return this.InvalidLanguages.length > 0;
				}
			},
			methods: {
				/**
				 * @param {string} language The language you want to check if it's valid or not
				 * @return {boolean}
				 */
				isInvalidLanguage: function ( language ) {
					return this.InvalidLanguages.indexOf( language ) > -1;
				}
			}
		};

		// adds the watch only for the property with the given name
		definition.watch[ watchedProperty ] = {
			handler: function ( termList ) {
				var InvalidLanguages = [],
					language;

				for ( var currentIndex = 0; currentIndex < termList.length; currentIndex++ ) {
					language = termList[ currentIndex ].language;

					if ( language === '' ) {
						continue; // don't validate blank values
					}

					if ( validLanguages.indexOf( language ) === -1 ) {
						InvalidLanguages.push( language );
					}
				}

				this.InvalidLanguages = InvalidLanguages;
				this.$emit( 'hasInvalidLanguage', this.hasInvalidLanguage );
			},
			deep: true,
			immediate: false
		};

		return definition;
	};

}() );
