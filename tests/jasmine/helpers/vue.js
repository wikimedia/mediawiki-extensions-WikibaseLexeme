/**
 * In production, both Vue and Vuex are loaded as ResourceLoader modules.
 * In the jasmine tests, we have to do that ourselves.
 */

/* eslint no-restricted-globals: 0 */
/* eslint no-implicit-globals: 0 */

var Vue = global.Vue = require( 'vue' );
var Vuex = global.Vuex = require( 'vuex' );
