<?php
namespace Wikibase\Lexeme\Search;

use CirrusSearch;
use CirrusSearch\Search\KeywordIndexField;
use SearchEngine;
use SearchIndexField;
use SearchIndexFieldDefinition;
use Wikibase\Repo\Search\Elastic\Fields\WikibaseIndexField;

/**
 * Keyword field for lexeme implementation
 */
abstract class LexemeKeywordField extends SearchIndexFieldDefinition implements WikibaseIndexField {

	public function __construct() {
		parent::__construct( static::NAME, \SearchIndexField::INDEX_TYPE_KEYWORD );
	}

	/**
	 * @param SearchEngine $engine
	 *
	 * @return array
	 */
	public function getMapping( SearchEngine $engine ) {
		// Since we need a specially tuned field, we can not use
		// standard search engine types.
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}

		$keyword = new KeywordIndexField( $this->getName(), $this->getIndexType(), $engine->getConfig() );
		$keyword->setFlag( self::FLAG_CASEFOLD );
		return $keyword->getMapping( $engine );
	}

	/**
	 * Produce specific field mapping
	 *
	 * @param SearchEngine $engine
	 * @param string $name
	 *
	 * @return SearchIndexField
	 */
	public function getMappingField( SearchEngine $engine, $name ) {
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return null;
		}
		return $this;
	}

}
