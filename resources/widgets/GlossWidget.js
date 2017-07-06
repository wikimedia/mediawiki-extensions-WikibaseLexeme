module.exports = ( function ( $, mw, require, Vue, Vuex ) {
	'use strict';

	function deepClone( object ) {
		return JSON.parse( JSON.stringify( object ) );
	}

	function applyGlossWidget( widgetElement, senseId, glosses ) {
		var store = new Vuex.Store( newGlossWidgetStore( glosses ) );
		var template = '#gloss-widget-vue-template';

		// eslint-disable-next-line no-new
		return new Vue( newGlossWidget( widgetElement, template, senseId, store ) );
	}

	function newGlossWidget( widgetElement, template, senseId, store ) {
		return {
			el: widgetElement,
			template: template,
			data: {
				inEditMode: false,
				senseId: senseId,
				glosses: deepClone( store.state.glosses )
			},
			computed: {
				isSaving: function () {
					return store.state.isSaving;
				}
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
				},
				save: function () {
					return store.dispatch( 'save', this.glosses ).then( function ( glosses ) {
						this.inEditMode = false;
						this.glosses = glosses;
					}.bind( this ) );
				},
				cancel: function () {
					this.inEditMode = false;
					this.glosses = deepClone( store.state.glosses );
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

	function newGlossWidgetStore( glosses ) {
		return {
			strict: true, //TODO make it configurable
			state: {
				isSaving: false,
				glosses: glosses
			},
			mutations: {
				startSaving: function ( state ) {
					state.isSaving = true;
				},
				finishSaving: function ( state ) {
					state.isSaving = false;
				},
				updateGlosses: function ( state, newGlosses ) {
					state.glosses = newGlosses;
				}
			},
			actions: {
				save: function ( context, glosses ) {
					if ( context.state.isSaving ) {
						throw new Error( 'Already saving!' );
					}
					context.commit( 'startSaving' );

					glosses = deepClone( glosses ); //TODO API call goes here
					return new Promise( function ( resolve, reject ) {
						setTimeout( function () {
							context.commit( 'updateGlosses', glosses );
							context.commit( 'finishSaving' );
							resolve( deepClone( context.state.glosses ) );
						}, 1000 );
					} );
				}
			}
		};
	}

	return {
		applyGlossWidget: applyGlossWidget,
		newGlossWidget: newGlossWidget,
		newGlossWidgetStore: newGlossWidgetStore
	};

} )( jQuery, mediaWiki, require, Vue, Vuex );
