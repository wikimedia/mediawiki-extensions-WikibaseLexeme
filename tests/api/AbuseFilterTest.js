'use strict';

const { requireExtensions } = require( '../../../Wikibase/tests/api-testing/utils' );
const { clientFactory, action, utils } = require( 'api-testing' );
const config = require( 'api-testing/lib/config' );
const {
	newAddLexemeStatementRequestBuilder,
	newCreateLexemeRequestBuilder
} = require( './helpers/RequestBuilderFactory' );
const { getItemId, getStringPropertyId } = require( './helpers/entityHelper' );
const { assertValidError } = require( './helpers/responseValidator' );
/**
 * AbuseFilter is used here to exercise the generic EditPrevented handling.
 *
 * AbuseFilter doesn't have an API to create filters. This is a very hacky way around the issue:
 * - get the edit token (a CSRF token salted for the AbuseFilter form)
 * - make a POST request that looks like it's coming from said form
 * - look up the new filter's ID
 *
 * @param {string} description
 * @param {string} rules
 * @return {Promise<string>} the filter ID
 */
async function createAbuseFilter( description, rules ) {
	const rootClient = await action.root();
	const client = clientFactory.getHttpClient( rootClient );

	const abuseFilterFormRequest = await client.get( `${ config.base_uri }index.php?title=Special:AbuseFilter/new` );
	const editToken = abuseFilterFormRequest.text
		.match( /value="[a-z0-9]+\+\\"/ )[ 0 ] // the token is in the value attribute of an input field and ends with +\
		.slice( 'value="'.length, -1 ); // remove parts that were matched that aren't part of the token

	await client.post( `${ config.base_uri }index.php` ).type( 'form' ).send( {
		title: 'Special:AbuseFilter/new',
		wpEditToken: editToken,
		wpFilterDescription: description,
		wpFilterRules: rules,
		wpFilterEnabled: 'true',
		wpFilterBuilder: 'other',
		wpFilterNotes: '',
		wpFilterWarnMessage: 'abusefilter-warning',
		wpFilterWarnMessageOther: 'abusefilter-warning',
		wpFilterActionDisallow: '',
		wpFilterDisallowMessage: 'abusefilter-disallowed',
		wpFilterDisallowMessageOther: 'abusefilter-disallowed',
		wpBlockAnonDuration: 'indefinite',
		wpBlockUserDuration: 'indefinite',
		wpFilterTags: ''
	} );

	const filters = await rootClient.list( 'abusefilters', { abfprop: 'id|description', abfdir: 'older' } );
	return filters.find( ( filter ) => filter.description === description ).id;
}

describe( 'Edit prevented with abuse filter', () => {

	const filterTriggerWord = utils.title( 'ABUSE-FILTER-TRIGGER-' );
	const filterDescription = `Filter: ${ filterTriggerWord }`;
	let filterId;
	let testLexicalCategory;
	let testLanguage;
	let testLexemeId;
	let testPropertyId;

	before( async function () {
		await requireExtensions( [ 'Abuse Filter' ] ).call( this );

		filterId = await createAbuseFilter( filterDescription, `"${ filterTriggerWord }" in new_wikitext` );
		testLexicalCategory = await getItemId();
		testLanguage = await getItemId();
		testLexemeId = ( await newCreateLexemeRequestBuilder( {
			lemmas: { en: `test-lemma-${ utils.uniq() }` },
			lexical_category: testLexicalCategory,
			language: testLanguage
		} ).makeRequest() ).body.id;
		testPropertyId = await getStringPropertyId();
	} );

	[
		() => newCreateLexemeRequestBuilder( {
			lemmas: { en: filterTriggerWord },
			lexical_category: testLexicalCategory,
			language: testLanguage
		} ),
		() => newAddLexemeStatementRequestBuilder(
			testLexemeId,
			{
				property: { id: testPropertyId },
				value: { type: 'value', content: filterTriggerWord }
			}
		)
	].forEach( ( newRequestBuilder ) => {
		it( `${ newRequestBuilder().getRouteDescription() } rejects edits matching an abuse filter`, async () => {
			const response = await newRequestBuilder().makeRequest();
			assertValidError( response, 403, 'permission-denied', {
				denial_reason: 'abusefilter-disallowed',
				denial_context: { abusefilter: {
					actions: [ 'disallow' ],
					description: filterDescription,
					id: filterId.toString()
				} }
			} );
		} );
	} );
} );
