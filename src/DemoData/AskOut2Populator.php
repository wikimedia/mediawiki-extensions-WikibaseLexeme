<?php

namespace Wikibase\Lexeme\DemoData;

use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Repo\Tests\NewStatement;

class AskOut2Populator {

	public function populate( Lexeme $lexeme ) {
		$defaultSense = $this->buildDefaultSense();

		$lexeme->setSenses( [ $defaultSense ] );
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildDefaultSense() {
		return NewSense::havingId( 'S5' )
			->withGloss( 'en', 'To request a romantic date' )
			->withStatement(
				NewStatement::forProperty( Id::P_GRAMMATICAL_FRAME )
					->withValue( 'to <ask> $somebody out' )
					->withSomeGuid()
			)
			->build();
	}

}
