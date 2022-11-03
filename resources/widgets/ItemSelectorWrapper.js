module.exports = ( function () {
	'use strict';

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @param {string} id
	 * @return {jQuery.Promise}
	 */
	function formatEntityLabel( api, id ) {
		return api.formatValue(
			{ value: { id: id }, type: 'wikibase-entityid' },
			{},
			'wikibase-item',
			'text/plain',
			''
		).then( function ( response ) {
			return response.result;
		} );
	}

	return function ( api ) {
		return {
			compatConfig: { MODE: 3 },
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

					// initialise entityselector with Q-id from value
					$input.data( 'entityselector' ).selectedEntity( vm.value );

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
			unmounted: function () {
				$( this.$el ).data( 'entityselector' ).destroy();
			}
		};
	};

}() );
