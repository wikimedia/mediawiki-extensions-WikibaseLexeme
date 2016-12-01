<?php

namespace Wikibase\Lexeme\DataModel\Providers;

use Wikibase\DataModel\Term\TermList;

/**
 * Common interface for classes that contain a TermList, representing lemmas.
 * This is guaranteed to return the original, mutable object by reference.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface LemmasProvider {

	/**
	 * This is guaranteed to return the original, mutable object by reference.
	 *
	 * @return TermList
	 */
	public function getLemmas();

}
