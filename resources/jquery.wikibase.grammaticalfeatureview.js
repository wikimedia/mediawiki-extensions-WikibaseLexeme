( function () {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	var GrammaticalFeatureListWidget = require( './widgets/GrammaticalFeatureListWidget.js' );

	/**
	 * @class jQuery.wikibase.grammaticalfeatureview
	 * @extends jQuery.ui.EditableTemplatedWidget
	 * @license GPL-2.0-or-later
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {string[]} options.value
	 * @param {wikibase.LabelFormattingService} options.labelFormattingService
	 * @param {mw.Api} options.api
	 */
	$.widget( 'wikibase.grammaticalfeatureview', PARENT, {
		options: {
			template: 'wikibase-lexeme-form-grammatical-features',
			templateShortCuts: {
				$header: '.wikibase-lexeme-form-grammatical-features-header',
				$values: '.wikibase-lexeme-form-grammatical-features-values'
			},
			labelFormattingService: null,
			api: null
		},

		_inEditMode: false,

		/**
		 * @type {wikibase.LabelFormattingService}
		 * @private
		 */
		_labelFormattingService: null,

		/**
		 * @type {GrammaticalFeatureListWidget}
		 * @private
		 */
		_grammaticalFeatureListWidget: null,

		value: function ( value ) {
			if ( typeof value !== 'undefined' ) {
				this.option( 'value', value );
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			return this._grammaticalFeatureListWidget.getValue();
		},

		_create: function () {
			PARENT.prototype._create.call( this );
			this._labelFormattingService = this.options.labelFormattingService;
			this.$header.attr( 'for', 'grammatical-features-' + this.uuid );
		},

		_startEditing: function () {
			this._inEditMode = true;
			return this.draw();
		},

		_stopEditing: function ( /* dropValue */ ) {
			this._inEditMode = false;

			return this.draw();
		},

		isInEditMode: function () {
			return this._inEditMode;
		},

		/**
		 * @inheritdoc
		 */
		draw: function () {
			var self = this;

			this.$header.text( mw.messages.get( 'wikibaselexeme-form-grammatical-features' ) );

			if ( this.isInEditMode() ) {
				return this._drawEdit();
			}

			var deferred = $.Deferred(),
				value = this.value() || [];

			if ( this._grammaticalFeatureListWidget ) {
				this._grammaticalFeatureListWidget.$element.remove();
			}

			this.$values.empty();
			value.forEach( function ( innerValue, i ) {
				var $el = $( '<span>' ).text( innerValue );
				self._labelFormattingService.getHtml( innerValue ).then( function ( html ) {
					$el.empty().append( html );
				} );

				if ( i > 0 ) {
					self.$values.append( ', ' ); // TODO i18n
				}
				self.$values.append( $el );
			} );

			return deferred.resolve().promise();
		},

		_drawEdit: function () {
			var deferred = $.Deferred();
			var self = this;

			var value = this.options.value || [];
			this._grammaticalFeatureListWidget = new GrammaticalFeatureListWidget( {
				api: self.options.api,
				labelFormattingService: this._labelFormattingService,
				language: mw.config.get( 'wgUserLanguage' ),
				placeholder: mw.messages.get( 'wikibaselexeme-grammatical-features-input-placeholder' ),
				inputId: this.$header.attr( 'for' ),
				options: value.map( function ( item ) {
					var $el = $( '<span>' ).text( item );
					self._labelFormattingService.getHtml( item ).then( function ( html ) {
						$el.empty().append( html );
					} );
					return {
						id: item,
						label: $el
					};
				} ),
				selected: value
			} );

			this._grammaticalFeatureListWidget.on( 'change', function () {
				self._trigger( 'change' );
			} );

			this.$values.empty().append( this._grammaticalFeatureListWidget.$element );
			return deferred.resolve().promise();
		}
	} );
}() );
