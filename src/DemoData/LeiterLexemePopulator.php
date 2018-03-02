<?php

namespace Wikibase\Lexeme\DemoData;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @license GPL-2.0-or-later
 */
class LeiterLexemePopulator {

	public function populate( Lexeme $lexeme ) {
		$leaderSense = $this->buildLeaderSense();
		$electricalConductorSense = $this->buildElectricalConductorSense();

		$lexeme->setSenses( [ $leaderSense, $electricalConductorSense ] );
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildLeaderSense() {
		return NewSense::havingId( 'S1' )
			->withGloss( 'de', 'Führungsperson' )
			->withGloss( 'en', 'leader' )
			->withStatement(
				NewStatement::forProperty( Id::P_DENOTES )
					->withValue( new ItemId( Id::Q_LEADER ) )
					->withSomeGuid()
			)
			->build();
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildElectricalConductorSense() {
		return NewSense::havingId( 'S2' )
			->withGloss( 'de', 'elektrischer Leiter' )
			->withGloss( 'en', 'electrical conductor' )
			->withStatement(
				NewStatement::forProperty( Id::P_DENOTES )
					->withValue( new ItemId( Id::Q_CONDUCTOR ) )
					->withSomeGuid()
			)
			->build();
	}

}
