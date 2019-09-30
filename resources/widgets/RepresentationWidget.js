module.exports = ( function ( Vuex ) {
	'use strict';

	var Vue = require( 'vue2' ),
		RedundantLanguageIndicator = require( 'wikibase.lexeme.widgets.RedundantLanguageIndicator' ),
		actionTypes = require( 'wikibase.lexeme.store.actionTypes' );

	Vue.use( Vuex );

	/**
	 * @callback RepresentationWidget.newComponent
	 *
	 * @param {Vuex.Store} store
	 * @param {integer} formIndex Index of the form to emit value updates on
	 * @param {string|HTMLElement} element - ID selector or DOM node
	 * @param {string} template - template string or ID selector
	 * @param {function} beforeUpdate
	 *
	 * @return {object} Vue component object
	 */
	function newComponent( store, formIndex, element, template, beforeUpdate, mw ) {

		return {
			el: element,
			template: template,

			store: store,

			mixins: [ RedundantLanguageIndicator( 'representations' ) ],

			beforeUpdate: beforeUpdate,

			data: {
				inEditMode: false,
				formIndex: formIndex
			},
			computed: {
				representations: function () {
					return this.$store.state.lexeme.forms[ this.formIndex ].representations;
				}
			},
			methods: {
				edit: function () {
					this.inEditMode = true;
					if ( this.representations.length === 0 ) {
						this.add();
					}
				},
				updateValue: function ( representation, event ) {
					this.$store.dispatch( actionTypes.UPDATE_REPRESENTATION_VALUE, {
						formIndex: this.formIndex,
						representationIndex: this.representations.indexOf( representation ),
						value: event.target.value
					} );
				},
				updateLanguage: function ( representation, event ) {
					this.$store.dispatch( actionTypes.UPDATE_REPRESENTATION_LANGUAGE, {
						formIndex: this.formIndex,
						representationIndex: this.representations.indexOf( representation ),
						language: event.target.value
					} );
				},
				stopEditing: function () {
					this.inEditMode = false;
				},
				add: function () {
					if ( !this.inEditMode ) {
						throw new Error( 'Cannot add representation if not in edit mode' );
					}

					this.$store.dispatch( actionTypes.ADD_REPRESENTATION, {
						formIndex: this.formIndex
					} );
				},
				remove: function ( representation ) {
					if ( !this.inEditMode ) {
						throw new Error( 'Cannot remove representation if not in edit mode' );
					}
					this.$store.dispatch( actionTypes.REMOVE_REPRESENTATION, {
						formIndex: this.formIndex,
						representationIndex: this.representations.indexOf( representation )
					} );
				}
			},
			filters: {
				message: function ( key ) {
					return mw.messages.get( key );
				}
			}
		};
	}

	/**
	 * @callback RepresentationWidget.create
	 *
	 * @param {Vuex.Store} store
	 * @param {integer} formIndex Index of the form to emit value updates on
	 * @param {string|HTMLElement} element - ID selector or DOM node
	 * @param {string} template - template string or ID selector
	 * @param {function} beforeUpdate
	 *
	 * @return {Vue} Initialized widget
	 */
	function create( store, formIndex, element, template, beforeUpdate, mw ) {
		return new Vue( newComponent( store, formIndex, element, template, beforeUpdate, mw ) );
	}

	/**
	 * @class RepresentationWidget
	 */
	return {
		create: create
	};

} )( Vuex );
