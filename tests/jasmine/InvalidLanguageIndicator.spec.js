describe( 'InvalidLanguageIndicator', function () {
	var expect = require( 'unexpected' ).clone(),
		sinon = require( 'sinon' );

	global.$ = require( 'jquery' ); // eslint-disable-line no-restricted-globals

	var InvalidLanguageIndicator = require( './../../resources/widgets/InvalidLanguageIndicator.js' );

	it( 'creates mixin definition that adds an InvalidLanguages property to data', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] );

		expect( mixin.data, 'to be a function' );
		expect( mixin.data(), 'to equal', { InvalidLanguages: [] } );
	} );

	it( 'creates mixin definition with watch on desired property', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] );

		expect( mixin.watch, 'to have property', 'myprop' );
	} );

	it( 'creates mixin definition with watch that does not fire immediately', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] );

		expect( mixin.watch.myprop.immediate, 'to be falsy' );
	} );

	it( 'creates mixin definition with watch that monitors the property recursively', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] );

		expect( mixin.watch.myprop.deep, 'to be truthy' );
	} );

	it( 'creates mixin definition providing method to determine if language isInvalidLanguage', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] );

		expect( mixin.methods.isInvalidLanguage, 'to be a function' );
	} );

	it( 'creates mixin definition method isInvalidLanguage returning false for empty InvalidLanguages', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] ),
			isInvalidLanguage = mixin.methods.isInvalidLanguage;

		expect( isInvalidLanguage.call( { InvalidLanguages: [] } ), 'to be falsy' );
	} );

	it( 'creates mixin definition providing computed property hasInvalidLanguage', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] );

		expect( mixin.computed.hasInvalidLanguage, 'to be a function' );
	} );

	it( 'creates mixin property hasInvalidLanguage returning false for empty InvalidLanguages', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] ),
			hasInvalidLanguage = mixin.computed.hasInvalidLanguage;

		expect( hasInvalidLanguage.call( { InvalidLanguages: [] } ), 'to be falsy' );
	} );

	it( 'creates mixin property hasInvalidLanguage returning true for existing InvalidLanguages', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [] ),
			hasInvalidLanguage = mixin.computed.hasInvalidLanguage;

		expect( hasInvalidLanguage.call( { InvalidLanguages: [ 'en' ] } ), 'to be truthy' );
	} );

	it( 'creates mixin watch handler that updates InvalidLanguages with respective language values', function () {
		var mixin = InvalidLanguageIndicator( 'myprop', [ 'fr' ] ),
			detectInvalidLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasInvalidLanguage() {
					return mixin.computed.hasInvalidLanguage.call( instance );
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
		var mixin = InvalidLanguageIndicator( 'myprop', [ 'de', 'en' ] ),
			detectInvalidLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasInvalidLanguage() {
					return mixin.computed.hasInvalidLanguage.call( instance );
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
		var mixin = InvalidLanguageIndicator( 'myprop', [ 'en' ] ),
			detectInvalidLanguages = mixin.watch.myprop.handler,
			instance = {
				get hasInvalidLanguage() {
					return mixin.computed.hasInvalidLanguage.call( instance );
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
