( function ( $ ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	/**
	 * @class jQuery.wikibase.lexemeformview
	 * @extends jQuery.ui.EditableTemplatedWidget
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
				},
				'',
				'Statements\' section will be here' //TODO find way to render block of statements
			],
			templateShortCuts: {
				$text: '.wikibase-lexeme-form-text',
				$id: '.wikibase-lexeme-form-id'
			},
			inputNodeName: 'TEXTAREA'
		},
		_inEditMode: false,

		/**
		 * This method acts as a setter if it is given a LexemeForm object.
		 * Otherwise it returns its value if it is not in edit mode and returns a new LexemeForm from its
		 * input value otherwise.
		 *
		 * @param {wikibase.lexeme.datamodel.LexemeForm} value
		 * @returns {wikibase.lexeme.datamodel.LexemeForm|undefined}
		 */
		value: function ( value ) {
			if ( value instanceof wb.lexeme.datamodel.LexemeForm ) {
				this.option( 'value', value );
				return;
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			return new wb.lexeme.datamodel.LexemeForm(
				Math.round( Math.random() * 100 ), // TODO: should be a unique numeric ID per form
				$.trim( this.$text.children( this.inputNodeName ).val() )
			);
		},
		_create: function () {
			PARENT.prototype._create.call( this );
			if ( !this.value() ) {
				this.startEditing();
			}
		},
		_startEditing: function () {
			// FIXME: This could be much faster
			this._inEditMode = true;
			return this.draw();
		},
		_stopEditing: function ( dropValue ) {
			this._inEditMode = false;
			if ( dropValue && this.options.value.getRepresentation() === '' ) {
				this.$text.children( this.inputNodeName ).val( '' );
			}

			return this.draw();
		},
		isInEditMode: function () {
			return this._inEditMode;
		},
		/**
		 * @inheritdoc
		 */
		draw: function () {
			var deferred = $.Deferred(),
				value = this.options.value;
			if ( !value || value.getRepresentation() === '' ) {
				value = null;
			}

			this.element.toggleClass( 'wb-empty', !value );

			if ( !this.isInEditMode() && !value ) {
				this.$text.text( mw.msg( 'wikibase-lexeme-empty-form-representation' ) );
				// Apply lang and dir of UI language
				// instead language of that row
				var userLanguage = mw.config.get( 'wgUserLanguage' );
				this.element
					.attr( 'lang', userLanguage )
					.attr( 'dir', $.util.getDirectionality( userLanguage ) );
				return deferred.resolve().promise();
			}

			if ( !this.isInEditMode() ) {
				this.$text.text( value.getRepresentation() );
				this.$id.text( ' (F' + value.getId() + ')' ); // TODO: whitespace and brackets (?) should be i18nable

				return deferred.resolve().promise();
			}

			var $input = $( document.createElement( this.options.inputNodeName ) )
				.attr( 'placeholder', mw.msg( 'wikibase-lexeme-enter-form-representation' ) );

			if ( value ) {
				$input.val( value.getRepresentation() );
			}

			if ( $.fn.inputautoexpand ) {
				$input.inputautoexpand( {
					suppressNewLine: true
				} );
			}

			this.$text.empty().append( $input );

			return deferred.resolve().promise();
		}
	} );
}( jQuery ) );
