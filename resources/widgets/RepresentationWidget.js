module.exports = ( function ( Vuex ) {
	'use strict';

	var Vue = require( 'vue' ),
		RedundantLanguageIndicator = require( './RedundantLanguageIndicator.js' ),
		actionTypes = require( '../store/actionTypes.js' ),
		focusElement = require( '../focusElement.js' );

	Vue.use( Vuex );

	/**
	 * @callback RepresentationWidget.newComponent
	 *
	 * @param {Vuex.Store} store
	 * @param {number} formIndex Index of the form to emit value updates on
	 * @param {string|HTMLElement} element - ID selector or DOM node
	 * @param {string} template - template string or ID selector
	 * @param {Function} beforeUpdate
	 *
	 * @return {Object} Vue component object
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
				formIndex: formIndex,
				// We need a way to identify each form input.
				// formIndex however is currently not incremented and awaiting refactoring.
				uid: Math.round( Math.random() * 1000000 )
			},
			computed: {
				representations: function () {
					return this.$store.state.lexeme.forms[ this.formIndex ].representations;
				}
			},
			methods: {
				inputId: function ( index, fieldId ) {
					return 'form' + this.uid + fieldId + index;
				},
				inputRepresentationId: function ( index ) {
					return this.inputId( index, 'inputRep' );
				},
				inputLanguageId: function ( index ) {
					return this.inputId( index, 'inputLang' );
				},
				edit: function () {
					this.inEditMode = true;
					if ( this.representations.length === 0 ) {
						this.add();
					} else {
						this.$nextTick( focusElement( 'input' ) );
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
					this.$nextTick( focusElement( 'li:nth-last-child(2) input' ) );
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
	 * @param {number} formIndex Index of the form to emit value updates on
	 * @param {string|HTMLElement} element - ID selector or DOM node
	 * @param {string} template - template string or ID selector
	 * @param {Function} beforeUpdate
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
