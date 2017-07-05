module.exports = ( function ( mw ) {
	'use strict';

	/**
	 * @callback wikibase.lexeme.widgets.RepresentationWidget.newComponent
	 *
	 * @param {{language:string, value: string}[]} representations
	 * @param {string|HTMLElement} element - ID selector or DOM node
	 * @param {string} template - template string or ID selector
	 * @param {function} beforeUpdate
	 *
	 * @return {object} Vue component object
	 */
	function newComponent( representations, element, template, beforeUpdate ) {
		return {
			el: element,
			template: template,

			beforeUpdate: beforeUpdate,

			data: {
				inEditMode: false,
				representations: representations
			},
			methods: {
				edit: function () {
					this.inEditMode = true;
					if ( this.representations.length === 0 ) {
						this.add();
					}
				},
				stopEditing: function () {
					this.inEditMode = false;
				},
				add: function () {
					if ( !this.inEditMode ) {
						throw new Error( 'Cannot add representation if not in edit mode' );
					}
					this.representations.push( { language: '', value: '' } );
				},
				remove: function ( representation ) {
					if ( !this.inEditMode ) {
						throw new Error( 'Cannot remove representation if not in edit mode' );
					}
					var index = this.representations.indexOf( representation );
					this.representations.splice( index, 1 );
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
	 * @callback wikibase.lexeme.widgets.RepresentationWidget.create
	 *
	 * @param {{language: string, value: string}[]} representations
	 * @param {string|HTMLElement} element - ID selector or DOM node
	 * @param {string} template - template string or ID selector
	 * @param {function} beforeUpdate
	 *
	 * @return {Vue} Initialized widget
	 */
	function create( representations, element, template, beforeUpdate ) {
		return new Vue( newComponent( representations, element, template, beforeUpdate ) );
	}

	/**
	 * @class wikibase.lexeme.widgets.RepresentationWidget
	 */
	return {
		create: create
	};

} )( mediaWiki );
