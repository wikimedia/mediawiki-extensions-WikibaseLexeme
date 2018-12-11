module.exports = ( function () {
	'use strict';

	var ValidLanguages = null,
		repoConfig = mw.config.get( 'wbRepo' ),
		repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';

	/**
	 * Vue mixin to provide detection of Term-like objects with invalid languages in an array
	 *
	 * @param {string} watchedProperty The property containing the term list on your component
	 *
	 * @return {object} Vue component object
	 */
	return function ( watchedProperty ) {
		var definition = {
			data: function () {
				return {
					InvalidLanguages: []
				};
			},
			watch: {
			},
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
				},
				/**
				 * @returns {}
				 */
				getValidLanguagesPromise: function () {
					if ( ValidLanguages === null ) {
						ValidLanguages = this.getValidLanguagesFromApi().then(
							function ( wbcontentlanguages ) {
								var validLanguages = [];
								for ( var key in wbcontentlanguages ) {
									validLanguages.push( wbcontentlanguages[ key ].code );
								}
								ValidLanguages = $.Deferred().resolve( validLanguages );
								return ValidLanguages;
							},
							function () {
								// On failure allow another request to take place
								ValidLanguages = null;
								return ValidLanguages;
							}
						);
					}
					return ValidLanguages;
				},
				getValidLanguagesFromApi: function () {
					var deferred = $.Deferred();
					$.ajax( {
						url: repoApiUrl,
						timeout: 2000,
						dataType: 'json',
						data: {
							action: 'query',
							format: 'json',
							meta: 'wbcontentlanguages',
							wbclcontext: 'term-lexicographical',
							wbclprop: 'code'
						}
					} )
						.done( function ( response ) {
							if ( response.error ) {
								deferred.reject( response.error.info );
								return;
							}

							deferred.resolve( response.query && response.query.wbcontentlanguages );
						} )
						.fail( function ( jqXHR, textStatus ) {
							deferred.reject( textStatus );
						} );

					return deferred;
				}
			}
		};

		// adds the watch only for the property with the given name
		definition.watch[ watchedProperty ] = {
			handler: detectInvalidLanguages,
			deep: true,
			immediate: false
		};

		return definition;
	};

	function detectInvalidLanguages( termList ) {
		var InvalidLanguages = [],
			language;

		this.getValidLanguagesPromise().then( ( function ( validLanguages ) {
			for ( var currentIndex = 0; currentIndex < termList.length; currentIndex++ ) {
				language = termList[ currentIndex ].language;

				if ( language === '' ) {
					continue; // don't validate blank values
				}

				if ( validLanguages !== null && $.inArray( language, validLanguages ) === -1 ) {
					InvalidLanguages.push( language );
				}
			}

			this.InvalidLanguages = InvalidLanguages;
			this.$emit( 'hasInvalidLanguage', this.hasInvalidLanguage );
		} ).bind( this ) );
	}

}() );
