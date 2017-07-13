module.exports = ( function ( wb, vv ) {
	'use strict';

	var PARENT = wb.experts.Entity;

	//Basically, copy-paste of src/PropertyType/FormIdFormatter.php:19-51
	//If you change something here, change also there
	var existingForms = [
		new Form( 'L13', 'hard English adjective', 'F1', 'hard', [ 'normative' ] ),
		new Form( 'L13', 'hard English adjective', 'F2', 'harder', [ 'comparative' ] ),
		new Form( 'L456', 'card English noun', 'F4', 'card', [ 'normative' ] ),
		new Form( 'L888', 'bard English noun', 'F1', 'bard', [ 'normative' ] ),
		new Form(
			'L14',
			'Leiter German noun',
			'F1',
			'Leiter',
			[ 'nominative', 'singular' ]
		),
		new Form(
			'L14',
			'Leiter German noun',
			'F2',
			'Leiters',
			[ 'genitive', 'singular' ]
		),
		new Form(
			'L14',
			'Leiter German noun',
			'F2',
			'Leiterin',
			[ 'nominative', 'singular', 'female' ]
		),
		new Form(
			'L17',
			'ask English noun',
			'F1',
			'ask',
			[]
		)
	];

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase Lexeme Form.
	 * @see jQuery.valueview.expert
	 * @see jQuery.valueview.Expert
	 * @class wikibase.experts.Form
	 * @extends wikibase.experts.Entity
	 * @license GPL-2.0+
	 */
	var SELF = vv.expert( 'wikibaselexemeform', PARENT, {
		/**
		 * @inheritdoc
		 */
		_init: function () {
			var viewState = this.viewState();
			var expert = this;

			//This hack is needed because value is string, but not an EntityId
			viewState.value = function fakeFormValueGetter() {

				//Another hack to display text in input field instead of HTML
				var inputValue = expert.$input.val();
				expert.$input.val( $( inputValue ).text() );

				if ( !this._value ) {
					return null;
				}

				var value = this._value;

				return {
					getSerialization: function () {
						return value;
					}
				};
			};

			PARENT.prototype._initEntityExpert.call( this );
		},
		_initEntityselector: function ( repoApiUrl ) {
			this.$input.entityselector( {
				url: repoApiUrl,
				type: this.constructor.TYPE,
				selectOnAutocomplete: true,
				source: function ( query ) {
					var found = existingForms.filter( function ( suggestion ) {
						return suggestion.match( query );
					} );
					return $.Deferred().resolve( found ).promise();
				}
			} );
		}
	} );

	/**
	 * @inheritdoc
	 */
	SELF.TYPE = 'lexeme-form';

	function Form(
		lexemeId,
		lemma,
		formId,
		representation,
		grammaticalFeatures
	) {
		this.repository = '';
		this.id = lexemeId + '-' + formId;
		this.description = grammaticalFeatures.join( ', ' );
		this.title = '(' + this.id + ') ' + this.description;
		this.pageid = 999;
		this.datatype = 'string';
		this.label = representation + ' (' + lemma + ')';
		this.match = { type: 'label', language: 'en', text: this.label };

		this.url = 'Lexeme:' + lexemeId + '#' + formId;
		this.concepturi = '/entity/' + lexemeId + '#' + formId;

		this.match = function ( query ) {
			var terms = [
				this.id,
				this.label
			];

			return terms.join().toLowerCase().indexOf( query.toLowerCase() ) >= 0;
		};
	}

	return SELF;

}( wikibase, jQuery.valueview ) );
