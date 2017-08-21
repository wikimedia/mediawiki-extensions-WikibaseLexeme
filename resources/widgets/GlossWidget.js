module.exports = ( function ( require, Vue ) {
	'use strict';

	function deepClone( object ) {
		return JSON.parse( JSON.stringify( object ) );
	}

	function applyGlossWidget( widgetElement, glosses, beforeUpdate, mw, getDirectionality ) {
		var template = '#gloss-widget-vue-template';

		return new Vue( newGlossWidget( widgetElement, template, glosses, beforeUpdate, mw, getDirectionality ) );
	}

	function newGlossWidget( widgetElement, template, glosses, beforeUpdate, mw, getDirectionality ) {
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
					this.glosses = this.glosses.filter( function ( gloss ) {
						return gloss.value.trim() !== '' && gloss.language.trim() !== '';
					} );
				}
			},
			filters: {
				message: function ( key ) {
					return mw.messages.get( key );
				},
				directionality: function ( languageCode ) {
					return getDirectionality( languageCode );
				}
			}
		};
	}

	return {
		applyGlossWidget: applyGlossWidget,
		newGlossWidget: newGlossWidget
	};

} )( require, Vue );
