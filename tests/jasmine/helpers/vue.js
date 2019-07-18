/**
 * vuex is provided by ResourceLoader, not required inside prod code
 *
 * vue is provided via require of "vue2" (name of the ResourceLoader module) in prod code.
 * The name "vue2" is made known to require during jasmine tests, where there is no ResourceLoader,
 * through a module alias (see package.json)
 */

/* eslint no-restricted-globals: 0 */
/* eslint no-implicit-globals: 0 */

var Vuex = global.Vuex = require( 'vuex/dist/vuex.js' );
