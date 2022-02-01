/**
 * @license GPL-2.0-or-later
 */
describe( 'ItemSelectorWrapper', function () {
	global.$ = require( 'jquery' ); // eslint-disable-line no-restricted-globals
	global.mw = { // eslint-disable-line no-restricted-globals
		config: {
			get: function () {
				return '';
			}
		}
	};
	var sinon = require( 'sinon' ),
		expect = require( 'unexpected' ).clone(),
		newItemSelectorWrapper = require( './../../resources/widgets/ItemSelectorWrapper.js' ),
		sandbox;

	beforeEach( function () {
		sandbox = sinon.createSandbox();
	} );

	afterEach( function () {
		sandbox.restore();
	} );

	it( 'passes the item ID to the entityselector widget on mount', function ( done ) {
		var itemId = 'Q123',
			component = newComponent( itemId ),
			mockEntitySelector = {
				selectedEntity: function ( valuePassedToEntitySelector ) {
					expect( valuePassedToEntitySelector, 'to be', itemId );

					// must be called for the test to pass
					done();
				},
				destroy: sinon.stub()
			};

		sandbox.stub( $.prototype, 'data' ).returns( mockEntitySelector )
		$.fn.entityselector = sinon.stub(); // pretend the entityselector widget exists

		component.mount( document.createElement( 'div' ) );
	} );

	function newComponent( value ) {
		return Vue.createApp(
			newItemSelectorWrapper( { formatValue: function()  {
				return $.Deferred().resolve( { result: {} } );
			} } ),
			{ value: value }
		);
	}

} );
