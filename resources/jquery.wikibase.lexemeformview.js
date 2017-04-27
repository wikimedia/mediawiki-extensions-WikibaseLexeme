( function ( $ ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	/**
	 * @class jQuery.wikibase.lexemeformsectionview
	 * @extends jQuery.ui.Widget
	 * @license GPL-2.0+
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {wikibase.datamodel.Forms} options.value
	 */
	$.widget( 'wikibase.lexemeformview', PARENT, {
		options: {
			template: 'wikibase-lexeme-form',
			templateParams: [
				function () {
					return 'some lang';
				},
				'',
				function () {
					return '';
				}
			],
			inputNodeName: 'TEXTAREA'
		},
		_inEditMode: false,

		value: function () {
			return this.element.text().trim();
		},
		_create: function () {
			PARENT.prototype._create.call( this );
			if (this.value() === '') {
				this.startEditing();
			}
		},
		_startEditing: function() {
			// FIXME: This could be much faster
			this._inEditMode = true;
			return this.draw();
		},
		_stopEditing: function( dropValue ) {
			this._inEditMode = false;
			if ( dropValue && this.options.value() === '' ) {
				this.$text.children( '.' + this.widgetFullName + '-input' ).val( '' );
			}

			return this.draw();
		},
		isInEditMode: function () {
			return this._inEditMode;
		},
		/**
		 * @inheritdoc
		 */
		draw: function() {
			var self = this,
				deferred = $.Deferred(),
				value = this.value();

			if ( value === '' ) {
				value = null;
			}

			this.element.toggleClass( 'wb-empty', !value );

			if ( !this.isInEditMode() && !value ) {
				this.$text.text( mw.msg( 'wikibase-label-empty' ) );
				// Apply lang and dir of UI language
				// instead language of that row
				var userLanguage = mw.config.get( 'wgUserLanguage' );
				this.element
					.attr( 'lang', userLanguage )
					.attr( 'dir', $.util.getDirectionality( userLanguage ) );
				return deferred.resolve().promise();
			}


			if ( !this.isInEditMode() ) {
				this.$text.text( value );
				return deferred.resolve().promise();
			}

			var $input = $( document.createElement( this.options.inputNodeName ) );

			$input
				.addClass( this.widgetFullName + '-input' )
				.on( 'keydown.' + this.widgetName, function( event ) {
					if ( event.keyCode === $.ui.keyCode.ENTER ) {
						event.preventDefault();
					}
				} )
				.on( 'eachchange.' + this.widgetName, function( event ) {
					self._trigger( 'change' );
				} );

			if ( value ) {
				$input.val( value );
			}

			if ( $.fn.inputautoexpand ) {
				$input.inputautoexpand( {
					expandHeight: true,
					suppressNewLine: true
				} );
			}

			this.element.empty().append( $input );

			return deferred.resolve().promise();
		}
	} );
}( jQuery ) );
