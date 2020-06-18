module.exports = ( function () {
	'use strict';

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {string} id
	 * @return {jQuery.Promise}
	 */
	function formatEntityLabel( api, id ) {
		var deferred = $.Deferred(),
			dataValue = { value: { id: id }, type: 'wikibase-entityid' };

		api.formatValue(
			dataValue,
			{},
			'wikibase-item',
			'text/plain',
			''
		).then( function ( response ) {
			deferred.resolve( response.result );
		} );

		return deferred.promise();
	}

	return function ( api ) {
		return {
			props: [ 'value' ],
			template: '<input>',
			mounted: function () {
				var vm = this,
					repoConfig = mw.config.get( 'wbRepo' ),
					repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php',
					expertType = 'item',
					$input = $( this.$el );

				formatEntityLabel( api, this.value ).then( function ( label ) {
					$input.val( label );

					$input.entityselector( {
						url: repoApiUrl,
						type: expertType,
						selectOnAutocomplete: true
					} );

					$input.on( 'entityselectorselected', function ( /* e */ ) {
						var entitySelector = $( vm.$el ).data( 'entityselector' ),
							selectedEntity = entitySelector.selectedEntity();

						var value = selectedEntity ? selectedEntity.id : '';

						vm.$emit( 'input', value );
					} );
				} );

			},
			watch: {
				value: function ( value ) {
					$( this.$el ).data( 'entityselector' ).selectedEntity( value );
				}
			},
			destroyed: function () {
				$( this.$el ).data( 'entityselector' ).destroy();
			}
		};
	};

} )();
