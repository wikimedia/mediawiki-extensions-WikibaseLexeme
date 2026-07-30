'use strict';

const { getLexemeId } = require( './entityHelper' );

/**
 * `describeWithTestData` is intended for testing behaviors across multiple related route categories
 * (e.g., all routes, all edit routes, or all GET routes) that share the same test data.
 *
 * **When to use:**
 * - When tests span a wide range of routes that require the same setup.
 * - When the consistency of shared test data across multiple describe blocks is crucial.
 *
 * **When NOT to use:**
 * - For testing a small number of specific routes or individual behaviors.
 * - When the indirection or built-in resets introduce unnecessary complexity or overhead.
 *
 * @param {string} testName
 * @param {Function} runAllTests
 * @return {void}
 */
function describeWithTestData( testName, runAllTests ) {
	const lexemeRequestInputs = {};

	function describeEachRoute( routes, runForEachRoute ) {
		routes.forEach( ( { newRequestBuilder, requestInputs } ) => {
			describe( newRequestBuilder().getRouteDescription(), () => {
				runForEachRoute( newRequestBuilder, requestInputs );
			} );
		} );
	}

	describe( testName, () => {
		before( async () => {
			lexemeRequestInputs.lexemeId = await getLexemeId();
		} );

		runAllTests( lexemeRequestInputs, describeEachRoute );
	} );
}

// eslint-disable-next-line mocha/no-exports
module.exports = { describeWithTestData };
