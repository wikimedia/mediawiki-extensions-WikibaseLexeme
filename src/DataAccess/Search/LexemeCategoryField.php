<?php
namespace Wikibase\Lexeme\DataAccess\Search;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;

/**
 * Lexeme lexical category field - this contains Q-id of lexeme category.
 */
class LexemeCategoryField extends LexemeKeywordField {
	const NAME = 'lexical_category';

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
		return $entity->getLexicalCategory()->getSerialization();
	}

}
