/**
 * @license GPL-2.0-or-later
 */
/**
 * @param {Object} wikibase Wikibase object
 */
( function () {
	'use strict';

	const wbui2025 = require( 'wikibase.wbui2025.lib' );

	/**
	 * Transform sense search results into menu items format
	 *
	 * @param {Array} searchResults Array of search results from API
	 * @return {Array} Array of menu items with label, value, and description
	 */
	const transformSubLexemeSearchResults = function ( searchResults ) {
		if ( !searchResults || searchResults.length === 0 ) {
			return [];
		}
		return searchResults.map( ( result ) => ( {
			label: result.description,
			value: result.id,
			description: result.label
		} ) );
	};

	class LexemeValueStrategy extends wbui2025.store.EntityValueStrategy {
		constructor( editSnakStore ) {
			super( editSnakStore, 'wikibase-lexeme' );
		}
	}
	class LexemePartStrategy extends wbui2025.store.EntityValueStrategy {
		transformSearchResults( data ) {
			if ( data.length === 0 ) {
				return [];
			}
			return transformSubLexemeSearchResults( data );
		}
	}
	class SenseValueStrategy extends LexemePartStrategy {
		constructor( editSnakStore ) {
			super( editSnakStore, 'wikibase-sense' );
		}
	}
	class FormValueStrategy extends LexemePartStrategy {
		constructor( editSnakStore ) {
			super( editSnakStore, 'wikibase-form' );
		}
	}

	/**
	 * Lexeme Registrations
	 */
	wbui2025.store.snakValueStrategyFactory.registerStrategyForDatatype(
		'wikibase-lexeme',
		( store ) => new LexemeValueStrategy( store ),
		( searchTerm ) => wbui2025.api.searchForEntities( searchTerm, 'lexeme' )
	);
	wbui2025.store.snakValueStrategyFactory.registerStrategyForDatatype(
		'wikibase-sense',
		( store ) => new SenseValueStrategy( store ),
		( searchTerm ) => wbui2025.api.searchForEntities( searchTerm, 'sense' )
	);
	wbui2025.store.snakValueStrategyFactory.registerStrategyForDatatype(
		'wikibase-form',
		( store ) => new FormValueStrategy( store ),
		( searchTerm ) => wbui2025.api.searchForEntities( searchTerm, 'form' )
	);

}(
	wikibase
) );
