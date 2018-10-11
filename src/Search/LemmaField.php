<?php
namespace Wikibase\Lexeme\Search;

use CirrusSearch;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\Repo\Search\Elastic\Fields\TermIndexField;

/**
 * Field implementing Lexeme's lemma
 */
class LemmaField extends TermIndexField {

	const NAME = 'lemma';

	public function __construct() {
		parent::__construct( static::NAME, \SearchIndexField::INDEX_TYPE_TEXT );
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

		$config = $this->getUnindexedField();
		$config['fields']['prefix'] =
			$this->getSubfield( 'prefix_asciifolding', 'near_match_asciifolding' );
		$config['fields']['near_match'] = $this->getSubfield( 'near_match' );
		$config['fields']['near_match_folded'] = $this->getSubfield( 'near_match_asciifolding' );
		// TODO: we don't seem to be using this, check if we need it?
		$config['copy_to'] = 'labels_all';

		return $config;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return mixed Get the value of the field to be indexed when a page/document
	 *               is indexed. This might be an array with nested data, if the field
	 *               is defined with nested type or an int or string for simple field types.
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( !( $entity instanceof Lexeme ) ) {
			return [];
		}
		/**
		 * @var Lexeme $entity
		 */
		return array_values( $entity->getLemmas()->toTextArray() );
	}

}
