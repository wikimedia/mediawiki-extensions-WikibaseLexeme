'use strict';

const { clientFactory } = require( 'api-testing' );
const basePath = '/rest.php/wikibase';

class RequestBuilder {

	constructor() {
		this.route = null;
		this.method = null;
		this.pathParams = {};
		this.queryParams = {};
		this.jsonBodyParams = {};
		this.headers = { 'user-agent': 'e2e tests' };
		this.user = null;
		this.configOverrides = {};
	}

	/**
	 * @param {string} method HTTP method to use for the request
	 * @param {string} route the route as it appears in the spec, e.g. '/v1/entities/items/{item_id}'
	 * @return {this}
	 */
	withRoute( method, route ) {
		this.method = method.toUpperCase();
		this.route = route;
		return this;
	}

	/**
	 * @param {string} name path param name, e.g. 'item_id' for /v1/entities/items/{item_id}
	 * @param {string} value
	 * @return {this}
	 */
	withPathParam( name, value ) {
		this.pathParams[ name ] = value;
		return this;
	}

	withQueryParam( name, value ) {
		this.queryParams[ name ] = value;
		return this;
	}

	withJsonBodyParam( name, value ) {
		this.headers[ 'content-type' ] = 'application/json';
		this.jsonBodyParams[ name ] = value;
		return this;
	}

	withEmptyJsonBody() {
		this.jsonBodyParams = {};
		return this;
	}

	withHeader( name, value ) {
		this.headers[ name.toLowerCase() ] = value;
		return this;
	}

	/**
	 * @param {Object} user e.g. `await action.mindy()`
	 * @return {this}
	 */
	withUser( user ) {
		this.user = user;
		return this;
	}

	/**
	 * @param {string} setting
	 * @param {*|Function} value - function arguments will be evaluated when makeRequest() is called
	 * @return {this}
	 */
	withConfigOverride( setting, value ) {
		this.configOverrides[ setting ] = value;
		return this;
	}

	async makeRequest() {
		const XDEBUG_SESSION = process.env.XDEBUG_SESSION;
		if ( XDEBUG_SESSION ) {
			this.withHeader( 'Cookie', `XDEBUG_SESSION=${ XDEBUG_SESSION }` );
		}

		let body = null;
		const contentType = this.headers[ 'content-type' ];
		switch ( contentType ) {
			case 'application/json':
				body = this.jsonBodyParams;
				break;
			case undefined:
				break;
			default:
				throw new Error( `${ this.constructor.name } doesn't support Content-Type '${ contentType }'` );
		}

		for ( const setting in this.configOverrides ) {
			this.configOverrides[ setting ] = this.configOverrides[ setting ] instanceof Function ?
				await this.configOverrides[ setting ]() :
				this.configOverrides[ setting ];
		}
		this.headers[ 'x-config-override' ] = JSON.stringify( this.configOverrides );

		const rest = clientFactory.getRESTClient( basePath, this.user );

		switch ( this.method.toUpperCase() ) {
			case 'GET':
				return rest.request( this.makePath(), this.method, this.queryParams, this.headers );
			case 'POST':
			case 'PUT':
			case 'PATCH':
			case 'DELETE':
				return rest.req[ this.method.toLowerCase() ]( basePath + this.makePath() )
					.set( this.headers )
					.query( this.queryParams )
					.send( body );
			default:
				throw new Error( `The "${ this.method }" method is not supported by ${ this.constructor.name }` );
		}

	}

	makePath() {
		let path = this.route;
		Object.keys( this.pathParams ).forEach( ( param ) => {
			path = path.replace( `{${ param }}`, this.pathParams[ param ] );
		} );

		if ( path.includes( '{' ) ) { // feels a bit hacky but should be ok?!
			throw new Error(
				`Path params "${ JSON.stringify( this.pathParams ) }" do not set all params in "${ this.route }".`
			);
		}

		return path;
	}

	getRouteDescription() {
		return this.method + ' ' + this.route;
	}

	getMethod() {
		return this.method;
	}
}

module.exports = { RequestBuilder };
