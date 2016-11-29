<?php

namespace Wikibase\Lexeme\DataModel\Providers;

use Wikibase\DataModel\Term\TermList;

/**
 * Common interface for classes that contain a TermList, representing lemmata.
 * This is guaranteed to return the original, mutable object by reference.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface LemmataProvider {

	/**
	 * This is guaranteed to return the original, mutable object by reference.
	 *
	 * @return TermList
	 */
	public function getLemmata();

}
