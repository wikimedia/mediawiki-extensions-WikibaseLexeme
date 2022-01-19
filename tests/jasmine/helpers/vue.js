/**
 * In production, both Vue and Vuex are loaded as ResourceLoader modules,
 * and the Vuex module already installs Vuex in Vue.
 * In the jasmine tests, none of that happens,
 * so we have to do the loading and installing ourselves.
 */

/* eslint no-restricted-globals: 0 */
/* eslint no-implicit-globals: 0 */

var Vue = global.Vue = require( 'vue/dist/vue.js' );
var Vuex = global.Vuex = require( 'vuex' );
Vue.use( Vuex );
