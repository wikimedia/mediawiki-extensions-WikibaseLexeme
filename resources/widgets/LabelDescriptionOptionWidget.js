( function ( wb ) {
	'use strict';

	/**
	 * @see OO.ui.MenuOptionWidget
	 *
	 * @param {Object} [config] contains id, label and description
	 *
	 * @license GPL-2.0-or-later
	 */
	var LabelDescriptionOptionWidget = function ( config ) {
		this._id = config.id;
		this._description = config.description;
		this._label = config.label;

		config.label = this.getLabel(); // overridden by getLabel
		OO.ui.MenuOptionWidget.call( this, config );
	};

	OO.inheritClass( LabelDescriptionOptionWidget, OO.ui.MenuOptionWidget );

	$.extend( LabelDescriptionOptionWidget.prototype, {
		getLabel: function () {
			var $option = $( '<div>' ).append(
				$( '<strong class="label">' ).text( this._label || this._id )
			);

			if ( this._description ) {
				$option.append(
					'<br>', $( '<span class="description">' ).text( this._description )
				);
			}

			return $option;
		},

		getInputLabel: function () {
			return this._label || this._id;
		}
	} );

	wb.lexeme.widgets.LabelDescriptionOptionWidget = LabelDescriptionOptionWidget;

}( wikibase ) );
