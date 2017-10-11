module.exports = ( function ( mw ) {
	'use strict';

	return {
		props: [ 'value' ],
		template: '<input>',
		mounted: function () {
			var vm = this,
				repoConfig = mw.config.get( 'wbRepo' ),
				repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php',
				expertType = 'item',
				$input = $( this.$el );

			$input.val( this.value );

			$input.entityselector( {
				url: repoApiUrl,
				type: expertType,
				selectOnAutocomplete: true
			} );

			$input.on( 'entityselectorselected', function ( e ) {
				var entitySelector = $( vm.$el ).data( 'entityselector' ),
					selectedEntity = entitySelector.selectedEntity();

				var value = selectedEntity ? selectedEntity.id : '';

				vm.$emit( 'input', value );
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
} )( mediaWiki );
