<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0+
 */
class TermListGenerator implements Generator {

	/**
	 * @var Generator
	 */
	private $termGenerator;

	public function __construct() {
		$this->termGenerator = new TermGenerator();
	}

	/**
	 * @see Generator::__invoke
	 *
	 * @param int $size
	 * @param callable $rand
	 *
	 * @return GeneratedValueSingle<T>
	 */
	public function __invoke( $size, $rand ) {
		$generateTerm = $this->termGenerator;

		$listSize = $rand( 0, $size );

		$result = new TermList( [] );

		$trials = 0;
		$maxTrials = 2 * $listSize;
		while ( $result->count() < $listSize && $trials < $maxTrials ) {
			$trials++;
			/** @var Term $term */
			$term = $generateTerm( $size, $rand )->unbox();
			if ( $result->hasTermForLanguage( $term->getLanguageCode() ) ) {
				continue;
			}

			$result->setTerm( $term );
		}

		return GeneratedValueSingle::fromJustValue( $result, 'TermList' );
	}

	/**
	 * @see Generator::shrink
	 *
	 * @param GeneratedValueSingle<T> $element
	 *
	 * @return GeneratedValueSingle<T>|GeneratedValueOptions<T>
	 */
	public function shrink( GeneratedValueSingle $element ) {
		return $element;
	}

	/**
	 * @param GeneratedValueSingle $element
	 *
	 * @return bool
	 */
	public function contains( GeneratedValueSingle $element ) {
		return $element->unbox() instanceof TermList;
	}

}
