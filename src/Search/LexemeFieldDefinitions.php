<?php

namespace Wikibase\Lexeme\Search;

use Wikibase\Repo\Search\Elastic\Fields\FieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\StatementCountField;
use Wikibase\Repo\Search\Elastic\Fields\WikibaseIndexField;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LexemeFieldDefinitions implements FieldDefinitions {

	/**
	 * @return WikibaseIndexField[]
	 */
	public function getFields() {
		return [
			'statement_count' => new StatementCountField()
		];
	}

}
