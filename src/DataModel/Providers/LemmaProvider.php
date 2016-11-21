<?php

namespace Wikibase\Lexeme\DataModel\Providers;

/**
 * Common interface for classes that contain a Term, representing lemma.
 * This is guaranteed to return the original.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface LemmaProvider {

	/**
	 * This is guaranteed to return the original.
	 *
	 * @return Term
	 */
	public function getLemma();

}
