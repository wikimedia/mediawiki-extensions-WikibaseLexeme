<?php

namespace Wikibase\Lexeme\Search;

use Wikibase\Repo\Search\Elastic\Fields\FieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\StatementCountField;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LexemeFieldDefinitions implements FieldDefinitions {

	/**
	 * @return SearchIndexField[]
	 */
	public function getFields() {
		$fields = [
			'statement_count' => new StatementCountField()
		];

		return $fields;
	}

}
