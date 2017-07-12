module.exports = ( function ( $, mw, require, Vue, Vuex ) {
	'use strict';

	function deepClone( object ) {
		return JSON.parse( JSON.stringify( object ) );
	}

	function applyGlossWidget( widgetElement, glosses, beforeUpdate ) {
		var template = '#gloss-widget-vue-template';

		return new Vue( newGlossWidget( widgetElement, template, glosses, beforeUpdate ) );
	}

	function newGlossWidget( widgetElement, template, glosses, beforeUpdate ) {
		return {
			el: widgetElement,
			template: template,

			beforeUpdate: beforeUpdate,

			data: {
				inEditMode: false,
				glosses: deepClone( glosses )
			},
			methods: {
				add: function () {
					this.glosses.push( { value: '', language: '' } );
				},
				remove: function ( gloss ) {
					var index = this.glosses.indexOf( gloss );
					this.glosses.splice( index, 1 );
				},
				edit: function () {
					this.inEditMode = true;
					if ( this.glosses.length === 0 ) {
						this.add();
					}
				},
				stopEditing: function () {
					this.inEditMode = false;
				}
			},
			filters: {
				message: function ( key ) {
					return mw.messages.get( key );
				},
				directionality: function ( languageCode ) {
					return $.util.getDirectionality( languageCode );
				}
			}
		};
	}

	return {
		applyGlossWidget: applyGlossWidget,
		newGlossWidget: newGlossWidget
	};

} )( jQuery, mediaWiki, require, Vue, Vuex );
