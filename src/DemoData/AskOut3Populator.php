<?php

namespace Wikibase\Lexeme\DemoData;

use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;

/**
 * @license GPL-2.0+
 */
class AskOut3Populator {

	public function populate( Lexeme $lexeme ) {
		$defaultSense = $this->buildDefaultSense();

		$lexeme->setSenses( [ $defaultSense ] );
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildDefaultSense() {
		return NewSense::havingId( 'S1' )
			->withGloss( 'en', 'To request a romantic date' )
			->build();
	}

}
