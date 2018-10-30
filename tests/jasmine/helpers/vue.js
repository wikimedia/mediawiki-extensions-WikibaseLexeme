// vue & vuex are provided by resource loader, not required inside prod code

/* eslint no-restricted-globals: 0 */
/* eslint no-implicit-globals: 0 */

var Vue = global.Vue = require( 'vue/dist/vue.js' ),
	Vuex = global.Vuex = require( 'vuex/dist/vuex.js' );

Vue.use( Vuex );
