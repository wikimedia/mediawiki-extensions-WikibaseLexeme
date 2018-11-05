<?php

namespace Wikibase\Lexeme\Presentation\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsNotAnItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemIdListDeserializer {

	/**
	 * @var ItemIdParser
	 */
	private $itemIdParser;

	public function __construct( ItemIdParser $itemIdParser ) {
		$this->itemIdParser = $itemIdParser;
	}

	/**
	 * @param mixed $serialization
	 * @param ValidationContext $validationContext
	 * @return ItemId[]
	 */
	public function deserialize( $serialization, ValidationContext $validationContext ) {
		$itemIdList = [];

		foreach ( $serialization as $index => $itemId ) {
			$context = $validationContext->at( $index );
			try {
				$itemIdList[] = $this->itemIdParser->parse( $itemId );
			} catch ( EntityIdParsingException $ex ) {
				$context->addViolation( new JsonFieldIsNotAnItemId( $itemId ) );
			}
		}

		return $itemIdList;
	}

}
