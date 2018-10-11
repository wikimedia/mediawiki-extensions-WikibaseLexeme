<?php
namespace Wikibase\Lexeme\DataAccess\Search;

use CirrusSearch\Search\ResultSet;
use Elastica\ResultSet as ElasticaResultSet;
use Language;

/**
 * Result set for Lexeme fulltext search
 */
class LexemeResultSet extends ResultSet {
	/**
	 * @var Language
	 */
	private $displayLanguage;
	/**
	 * @var LexemeDescription
	 */
	private $descriptionMaker;
	/**
	 * Pre-processed results from Lexeme search, as raw data -
	 * not yet localized and without description generated.
	 * @var array
	 */
	private $rawResults;

	/**
	 * @param Language $displayLanguage
	 * @param LexemeDescription $descriptionMaker
	 * @param array $lexemeResults Pre-processed data from Lexeme
	 */
	public function __construct(
		ElasticaResultSet $ESresult,
		Language $displayLanguage,
		LexemeDescription $descriptionMaker,
		array $lexemeResults
	) {
		parent::__construct( [], [], $ESresult, false );
		$this->displayLanguage = $displayLanguage;
		$this->descriptionMaker = $descriptionMaker;
		$this->rawResults = $lexemeResults;
	}

	/**
	 * @return \SearchResult[]
	 */
	public function extractResults() {
		if ( !$this->rawResults ) {
			return [];
		}
		if ( $this->results === null ) {
			$this->results = array_map( function ( array $current ) {
				$result = new LexemeResult( $this->displayLanguage, $this->descriptionMaker, $current );
				$this->augmentResult( $result );
				return $result;
			}, $this->rawResults );
		}
		return $this->results;
	}

	/**
	 * Get raw results.
	 * Used in testing.
	 * @return array
	 */
	public function getRawResults() {
		return $this->rawResults;
	}

}
