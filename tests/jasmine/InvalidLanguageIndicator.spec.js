describe( 'wikibase.lexeme.widgets.InvalidLanguageIndicator', function () {
	var expect = require( 'unexpected' ).clone(),
		sinon = require( 'sinon' );

	var $ = global.jQuery = require( 'jquery' ); // eslint-disable-line no-restricted-globals
	global.mediaWiki = { // eslint-disable-line no-restricted-globals
		config: {
			get: function ( key ) {
				switch ( key ) {
				case 'wbRepo':
					return {
						url: 'http://localhost',
						scriptPath: 'w/'
					};
				default:
					throw new Error( 'unknown config key: ' + key );
				}
			}
		}
	};

	var InvalidLanguageIndicator = require( 'wikibase.lexeme.widgets.InvalidLanguageIndicator' );

	it( 'creates mixin definition that adds an InvalidLanguages property to data', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' );

		expect( mixin.data, 'to be a function' );
		expect( mixin.data(), 'to equal', { InvalidLanguages: [] } );
	} );

	it( 'creates mixin definition with watch on desired property', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' );

		expect( mixin.watch, 'to have property', 'myprop' );
	} );

	it( 'creates mixin definition with watch that does not fire immediately', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' );

		expect( mixin.watch.myprop.immediate, 'to be falsy' );
	} );

	it( 'creates mixin definition with watch that monitors the property recursively', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' );

		expect( mixin.watch.myprop.deep, 'to be truthy' );
	} );

	it( 'creates mixin definition providing method to determine if language isInvalidLanguage', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' );

		expect( mixin.methods.isInvalidLanguage, 'to be a function' );
	} );

	it( 'creates mixin definition method isInvalidLanguage returning false for empty InvalidLanguages', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' ),
			isInvalidLanguage = mixin.methods.isInvalidLanguage;

		expect( isInvalidLanguage.call( { InvalidLanguages: [] } ), 'to be falsy' );
	} );

	it( 'creates mixin definition providing computed property hasInvalidLanguage', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' );

		expect( mixin.computed.hasInvalidLanguage, 'to be a function' );
	} );

	it( 'creates mixin property hasInvalidLanguage returning false for empty InvalidLanguages', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' ),
			hasInvalidLanguage = mixin.computed.hasInvalidLanguage;

		expect( hasInvalidLanguage.call( { InvalidLanguages: [] } ), 'to be falsy' );
	} );

	it( 'creates mixin property hasInvalidLanguage returning true for existing InvalidLanguages', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' ),
			hasInvalidLanguage = mixin.computed.hasInvalidLanguage;

		expect( hasInvalidLanguage.call( { InvalidLanguages: [ 'en' ] } ), 'to be truthy' );
	} );

	it( 'creates mixin watch handler that updates InvalidLanguages with respective language values', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' ),
			detectInvalidLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasInvalidLanguage() {
					return mixin.computed.hasInvalidLanguage.call( instance );
				},
				getValidLanguagesPromise: function () {
					return {
						then: function ( callback ) {
							callback( [ 'fr' ] );
						}
					};
				},
				$emit: sinon.spy()
			};

		detectInvalidLanguages.call(
			instance,
			[
				{ language: 'en', value: 'something' },
				{ language: 'fr', value: 'autre' }
			]
		);
		expect( instance.InvalidLanguages, 'to equal', [ 'en' ] );
		expect( instance.$emit.withArgs( 'hasInvalidLanguage', true ).calledOnce, 'to be truthy' );
	} );

	it( 'creates mixin watch handler that can find multiple invalid languages', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' ),
			detectInvalidLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasInvalidLanguage() {
					return mixin.computed.hasInvalidLanguage.call( instance );
				},
				getValidLanguagesPromise: function () {
					return {
						then: function ( callback ) {
							callback( [ 'de', 'en' ] );
						}
					};
				},
				$emit: sinon.spy()
			};

		detectInvalidLanguages.call(
			instance,
			[
				{ language: 'haha', value: 'color' },
				{ language: 'de', value: 'Farbe' },
				{ language: 'en', value: 'colour' },
				{ language: 'lol', value: 'Kolorierung' }
			]
		);
		expect( instance.InvalidLanguages, 'to equal', [ 'haha', 'lol' ] );
		expect( instance.$emit.withArgs( 'hasInvalidLanguage', true ).calledOnce, 'to be truthy' );
	} );

	it( 'creates mixin watch handler not taking offence in empty language', function () {
		var mixin = InvalidLanguageIndicator( 'myprop' ),
			detectInvalidLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasInvalidLanguage() {
					return mixin.computed.hasInvalidLanguage.call( instance );
				},
				getValidLanguagesPromise: function () {
					return {
						then: function ( callback ) {
							callback( [ 'en' ] );
						}
					};
				},
				$emit: sinon.spy()
			};

		detectInvalidLanguages.call(
			instance,
			[
				{ language: 'en', value: 'example' },
				{ language: '', value: '' },
				{ language: '', value: '' }
			]
		);
		expect( instance.InvalidLanguages, 'to equal', [] );
		expect( instance.$emit.withArgs( 'hasInvalidLanguage', false ).calledOnce, 'to be truthy' );
	} );

} );
