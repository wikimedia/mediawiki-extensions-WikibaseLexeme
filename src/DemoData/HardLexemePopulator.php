<?php

namespace Wikibase\Lexeme\DemoData;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @license GPL-2.0+
 */
class HardLexemePopulator {

	public function populate( Lexeme $lexeme ) {
		$presentingDifficultySense = $this->buildPresentingDifficultySense();
		$resistingDeformationSense = $this->buildResistingDeformationSense();

		$lexeme->setSenses( [ $presentingDifficultySense, $resistingDeformationSense ] );
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildPresentingDifficultySense() {
		return NewSense::havingId( 'S1' )
			->withGloss( 'en', 'presenting difficulty' )
			->withGloss( 'de', 'Schwierig oder kompliziert' )
			->withStatement(
				NewStatement::forProperty( Id::P_SYNONYM )
					->withValue( Id::LS_DIFFICULT )
					->withSomeGuid()
			)
			->withStatement(
				NewStatement::forProperty( Id::P_REGISTER )
					->withValue( new ItemId( Id::Q_COLLOQUIALISM ) )
					->withSomeGuid()
			)
			->withStatement(
				NewStatement::forProperty( Id::P_TRANSLATION )
					->withValue( Id::LS_SCHWIERIG )
					->withSomeGuid()
			)
			->withStatement(
				NewStatement::forProperty( Id::P_TRANSLATION )
					->withValue( Id::LS_DUR )
					->withSomeGuid()
			)
			->withStatement(
				NewStatement::forProperty( Id::P_ANTONYM )
					->withValue( Id::LS_EASY )
					->withSomeGuid()
			)
			->withStatement(
				NewStatement::forProperty( Id::P_ANTONYM )
					->withValue( Id::LS_SIMPLE )
					->withSomeGuid()
			)
			->build();
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildResistingDeformationSense() {
		return NewSense::havingId( 'S2' )
			->withGloss( 'en', 'resisting deformation' )
			->withGloss( 'de', 'schwer verformbar' )
			->withStatement(
				NewStatement::forProperty( Id::P_EVOKES )
					->withValue( new ItemId( Id::Q_ELASTICITY ) )
					->withSomeGuid()
			)
			->withStatement(
				NewStatement::forProperty( Id::P_EVOKES )
					->withValue( new ItemId( Id::Q_DUCTILITY ) )
					->withSomeGuid()
			)
			->withStatement(
				NewStatement::forProperty( Id::P_EVOKES )
					->withValue( new ItemId( Id::Q_HARDNESS ) )
					->withSomeGuid()
			)
			->withStatement(
				NewStatement::forProperty( Id::P_ANTONYM )
					->withValue( Id::LS_SOFT )
					->withSomeGuid()
			)
			->build();
	}

}
