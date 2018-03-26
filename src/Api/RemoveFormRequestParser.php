<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\Api\Constraint\RemoveFormConstraint;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormRequestParser {

	const PARAM_FORM_ID = 'id';
	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var ItemIdParser
	 */
	private $itemIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
		$this->itemIdParser = new ItemIdParser();
	}

	/**
	 * @param array $params
	 * @return RemoveFormRequestParserResult
	 */
	public function parse( array $params ) {
		$errors = $this->validate( $params );

		if ( $errors ) {
			return RemoveFormRequestParserResult::newWithErrors( $errors );
		}

		return RemoveFormRequestParserResult::newWithRequest(
			new RemoveFormRequest( $this->entityIdParser->parse( $params[self::PARAM_FORM_ID] ) )
		);
	}

	private function validate( $params ) {
		$validator = new ApiRequestValidator();
		return $validator->convertViolationsToApiErrors(
			$validator->validate( $params, RemoveFormConstraint::one() )
		);
	}

}
