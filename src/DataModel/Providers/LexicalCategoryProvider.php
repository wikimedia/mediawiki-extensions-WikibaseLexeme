<?php

namespace Wikibase\Lexeme\DataModel\Providers;

use Wikibase\DataModel\Entity\ItemId;

/**
 * Common interface for classes that contain an Item, representing lexical category.
 * This is guaranteed to return the original, mutable object by reference.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface LexicalCategoryProvider {

	/**
	 * This is guaranteed to return the original, mutable object by reference.
	 *
	 * @return ItemId
	 */
	public function getLexicalCategory();

}
