( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	var GlossWidget = require( './widgets/GlossWidget.js' ),
		LexemeSubEntityId = require( './datamodel/LexemeSubEntityId.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * Initializes StatementGroupListView on given DOM element
	 *
	 * @callback buildStatementGroupListView
	 * @param {wikibase.lexeme.datamodel.Sense}
	 * @param {jQuery} JQuery DOM element
	 */

	/**
	 * @class jQuery.wikibase.senseview
	 * @extends jQuery.ui.Widget
	 * @license GPL-2.0-or-later
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 */
	$.widget( 'wikibase.senseview', PARENT, {
		options: {
			template: 'wikibase-lexeme-sense',
			templateParams: [
				function () {
					var $container = $( '<span>' );
					this.deferredSenseWithId.promise().then( function ( sense ) {
						$container.text( sense.getId() );
					} );

					return $container;
				},
				function () {
					return $( '<div>' ).addClass( 'wikibase-lexeme-sense-glosses' );
				},
				function () {
					var $container = $( '<div>' );
					this.deferredSenseWithId.promise().then( function ( sense ) {
						var messageKey = 'wikibaselexeme-statementsection-statements-about-sense';
						var $header = $( '<h2>' ).applyTemplate(
							'wb-section-heading',
							[
								// eslint-disable-next-line mediawiki/msg-doc
								mw.message( messageKey, sense.getId() ).escaped(),
								'',
								'wikibase-statements'
							]
						);
						$container.append( $header );

						var $statements = $( '<div>' );
						this.options.buildStatementGroupListView(
							sense,
							$statements,
							LexemeSubEntityId.getIdSuffix( sense.getId() )
						);
						$container.append( $statements );
					}.bind( this ) );

					return $container;
				},
				function () { // We can't mangle these directly, thus change them via DOM.
					this.deferredSenseWithId.promise().then( function ( sense ) {
						this.element.attr( 'id', LexemeSubEntityId.getIdSuffix( sense.getId() ) );
						this.element.data( 'sense-id', sense.getId() );
					}.bind( this ) );

					return '';
				}
			],

			/**
			 * @type {buildStatementGroupListView}
			 */
			buildStatementGroupListView: null
		},

		_inEditMode: false,

		glossWidget: null,

		/**
		 * This method acts as a setter if it is given a Sense object.
		 * Otherwise it returns its value.
		 *
		 * @param {wikibase.lexeme.datamodel.Sense} sense
		 * @return {wikibase.lexeme.datamodel.Sense|undefined}
		 */
		value: function ( sense ) {
			if ( sense instanceof wb.lexeme.datamodel.Sense ) {
				this.option( 'value', sense );
				this.glossWidget.glosses = termMapToArray( sense.getGlosses() );
				if ( this.deferredSenseWithId && sense.getId() ) {
					this.deferredSenseWithId.resolve( sense );
					this.deferredSenseWithId = null;
				}
				this.draw();
				return;
			}

			if ( !this.isInEditMode() ) {
				return this.options.value;
			}

			if ( this.glossWidget.hasInvalidLanguage || this.glossWidget.hasRedundantLanguage ) {
				return null;
			}

			return new wb.lexeme.datamodel.Sense(
				this.options.value ? this.options.value.getId() : null,
				arrayToTermMap( this.glossWidget.glosses )
			);
		},

		_create: function () {
			this.deferredSenseWithId = $.Deferred();

			PARENT.prototype._create.call( this );

			this.options.buildStatementGroupListView(
				this.value(),
				$( '.wikibase-statementgrouplistview', this.element ),
				this.value().getId() === undefined ? '' : LexemeSubEntityId.getIdSuffix( this.value().getId() )
			);

			this.glossWidget = GlossWidget.applyGlossWidget(
				$( '.wikibase-lexeme-sense-glosses', this.element )[ 0 ],
				termMapToArray( this.value().getGlosses() ),
				function () {
					this._trigger( 'change' );
				}.bind( this ),
				mw,
				$.util.getDirectionality
			);
		},

		_startEditing: function () {
			this._inEditMode = true;
			this.glossWidget.edit();

			return this.draw();
		},

		_stopEditing: function ( dropValue ) {
			this._inEditMode = false;
			this.glossWidget.stopEditing();
			if ( dropValue ) {
				this.glossWidget.glosses = termMapToArray(
					this.value().getGlosses()
				);
			}

			return this.draw();
		},

		/**
		 * @inheritdoc
		 */
		draw: function () {
			var deferred = $.Deferred(),
				value = this.options.value;

			if ( !this.isInEditMode() && !value ) {
				// Apply lang and dir of UI language
				// instead language of that row
				var userLanguage = mw.config.get( 'wgUserLanguage' );
				this.element
					.attr( 'lang', userLanguage )
					.attr( 'dir', $.util.getDirectionality( userLanguage ) );
				return deferred.resolve().promise();
			}

			return deferred.resolve().promise();
		},

		isInEditMode: function () {
			return this._inEditMode;
		}

	} );

	function arrayToTermMap( glosses ) {
		var result = new datamodel.TermMap();

		glosses.forEach( function ( gloss ) {
			try {
				result.setItem(
					gloss.language,
					new datamodel.Term( gloss.language, gloss.value )
				);
			} catch ( e ) {
				// ignore
			}
		} );

		return result;
	}

	/**
	 * @param {datamodel.TermMap} glosses
	 * @return {Array}
	 */
	function termMapToArray( glosses ) {
		var result = [];

		glosses.each( function ( language, term ) {
			result.push( { language: term.getLanguageCode(), value: term.getText() } );
		} );

		result.sort( function ( a, b ) {
			return a.language > b.language;
		} );

		return result;
	}

}( wikibase ) );
