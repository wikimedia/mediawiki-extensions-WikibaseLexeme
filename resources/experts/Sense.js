module.exports = ( function ( wb, vv ) {
	'use strict';

	var PARENT = wb.experts.Entity;

	//Basically, copy-paste of src/PropertyType/SenseIdFormatter.php:18-48
	//If you change something here, change also there
	var existingSenses = [
		new Sense( 'L13', 'hard', 'English adjective', 'S1', 'presenting difficulty' ),
		new Sense( 'L13', 'hard', 'English adjective', 'S2', 'resisting deformation' ),
		new Sense( 'L3627', 'difficult', 'English adjective', 'S4', 'not easy, requiring skill' ),
		new Sense( 'L283', 'schwierig', 'German adjective', 'S2', 'complicated' ),
		new Sense( 'L465', 'dur', 'French adjective', 'S1', 'hard' ),
		new Sense( 'L801', 'easy', 'English adjective', 'S1', 'not difficult' ),
		new Sense( 'L802', 'simple', 'English adjective', 'S1', 'not difficult' ),
		new Sense( 'L803', 'soft', 'English adjective', 'S1', 'easy to deform' ),
		new Sense( 'L15', 'Leiter', 'German noun', 'S1', 'leader' ),
		new Sense( 'L15', 'Leiter', 'German noun', 'S1', 'electrical conductor' ),
		new Sense(
			'L17',
			'ask',
			'English verb',
			'S5',
			'\'To ask somebody out\': To request a romantic date'
		),
		new Sense( 'L18', 'ask', 'English verb', 'S5', 'To request a romantic date' ),
		new Sense( 'L19', 'ask out', 'English verbal phrase', 'S1', 'To request a romantic date' )
	];

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase Lexeme Sense.
	 * @see jQuery.valueview.expert
	 * @see jQuery.valueview.Expert
	 * @class wikibase.experts.Sense
	 * @extends wikibase.experts.Entity
	 * @license GPL-2.0-or-later
	 */
	var SELF = vv.expert( 'wikibaselexemesense', PARENT, {
		/**
		 * @inheritdoc
		 */
		_init: function () {
			var viewState = this.viewState();
			var expert = this;

			//This hack is needed because value is string, but not an EntityId
			viewState.value = function fakeSenseValueGetter() {

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
					var found = existingSenses.filter( function ( suggestion ) {
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
	SELF.TYPE = 'sense';

	function Sense(
		lexemeId,
		lemma,
		lexemeDescription,
		senseId,
		gloss
	) {
		this.repository = '';
		this.id = lexemeId + '-' + senseId;
		this.pageid = 999;
		this.datatype = 'string';
		this.lemma = lemma;

		this.label = lemma + ' (' + this.id + '): ' + gloss;
		this.description = lexemeDescription;
		this.title = '(' + this.id + ') ' + lexemeDescription + ' - ' + gloss;

		this.url = './Lexeme:' + lexemeId + '#' + senseId;
		this.concepturi = '/entity/' + lexemeId + '#' + senseId;

		this.match = { type: 'label', language: 'en', text: this.label };

		this.match = function ( query ) {
			var terms = [
				this.id,
				this.lemma
			];

			return terms.join( ' ' ).toLowerCase().indexOf( query.toLowerCase() ) >= 0;
		};
	}

	return SELF;

}( wikibase, jQuery.valueview ) );
