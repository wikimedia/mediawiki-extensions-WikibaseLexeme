<?php

namespace Wikibase\Lexeme\Domain\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikimedia\Assert\Assert;

/**
 * Extracts the item id of the lexical category of a lexeme
 *
 * @license GPL-2.0-or-later
 */
class LexicalCategoryItemIdExtractor implements EntityReferenceExtractor {

	/**
	 * @param EntityDocument $lexeme
	 * @return ItemId[]
	 */
	public function extractEntityIds( EntityDocument $lexeme ) {
		Assert::parameterType( Lexeme::class, $lexeme, '$lexeme' );
		'@phan-var Lexeme $lexeme';

		/** @var Lexeme $lexeme */
		return [ $lexeme->getLexicalCategory() ];
	}

}
