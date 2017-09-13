<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

class TermListGenerator implements Generator {

	/**
	 * @var TermGenerator
	 */
	private $termGenerator;

	public function __construct() {
		$this->termGenerator = new TermGenerator();
	}

	/**
	 * @param int The generation size
	 * @param callable  a rand() function
	 * @return GeneratedValueSingle<T>
	 */
	public function __invoke( $size, $rand ) {
		$listSize = $rand( 0, $size );

		$result = new TermList( [] );

		$trials = 0;
		$maxTrials = 2 * $listSize;
		while ( $result->count() < $listSize && $trials < $maxTrials ) {
			$trials++;
			/** @var Term $term */
			$term = $this->termGenerator->__invoke( $size, $rand )->unbox();
			if ( $result->hasTermForLanguage( $term->getLanguageCode() ) ) {
				continue;
			}
			$result->setTerm( $term );
		}

		return GeneratedValueSingle::fromJustValue( $result, 'TermList' );
	}

	/**
	 * The conditions for terminating are either:
	 * - returning the same GeneratedValueSingle passed in
	 * - returning an empty GeneratedValueOptions
	 *
	 * @param GeneratedValueSingle<T>
	 * @return GeneratedValueSingle<T>|GeneratedValueOptions<T>
	 */
	public function shrink( GeneratedValueSingle $element ) {
		return $element;
	}

	/**
	 * @param GeneratedValueSingle
	 * @return bool
	 */
	public function contains( GeneratedValueSingle $element ) {
		return $element->unbox() instanceof TermList;
	}

}
