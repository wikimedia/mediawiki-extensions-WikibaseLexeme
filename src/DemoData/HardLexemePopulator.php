<?php

namespace Wikibase\Lexeme\DemoData;

use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Repo\Tests\NewStatement;

class HardLexemePopulator {

	public function populate( Lexeme $lexeme ) {
		$hardForm = $this->buildHardForm();
		$harderForm = $this->buildHarderForm();

		$presentingDifficultySense = $this->buildPresentingDifficultySense();
		$resistingDeformationSense = $this->buildResistingDeformationSense();

		$lexeme->setForms( [ $hardForm, $harderForm ] );
		$lexeme->setSenses( [ $presentingDifficultySense, $resistingDeformationSense ] );
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Form
	 */
	private function buildHardForm() {
		return NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'hard' )
			->andGrammaticalFeature( Id::Q_NORMATIVE )
			->andStatement(
				NewStatement::forProperty( Id::P_IPA_PRONUNCIATION )
					->withValue( '/hɑːd/' )
			)->andStatement(
				NewStatement::forProperty( Id::P_IPA_PRONUNCIATION )
					->withValue( '/hɑɹd/' )
			)->andStatement(
				NewStatement::forProperty( Id::P_PRONUNCIATION_AUDIO )
					->withValue( 'hard.ogg' )
			)->build();
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Form
	 */
	private function buildHarderForm() {
		return NewForm::havingId( 'F2' )
			->andRepresentation( 'en', 'harder' )
			->andGrammaticalFeature( Id::Q_COMPARATIVE )
			->build();
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildPresentingDifficultySense() {
		return NewSense::havingId( 'S1' )
			->withGloss( 'en', 'presenting difficulty' )
			->withGloss( 'de', 'Schwierig oder kompliziert' )
//			->withStatement(
//				NewStatement::forProperty(Id::P_SYNONYM_OF)
//				->withValue(Id::___difficult)
//			)
			->withStatement(
				NewStatement::forProperty( Id::P_REGISTER )
					->withValue( Id::Q_COLLOQUIALISM )
			)
//			->withStatement(
//				NewStatement::forProperty(Id::P_Translation)
//				->withValue(Id::S_schwierig)
//			)
			->build();
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildResistingDeformationSense() {
		return NewSense::havingId( 'S2' )
			->withGloss( 'en', 'resisting deformation' )
			->withGloss( 'de', 'schwer verformbar' )
//			->withStatement(
//				NewStatement::forProperty(Id::P_SYNONYM_OF)
//				->withValue(Id::___difficult)
//			)
			->withStatement(
				NewStatement::forProperty( Id::P_RELATED_CONCEPT )
					->withValue( Id::Q_ELASTICITY )
			)
			->withStatement(
				NewStatement::forProperty( Id::P_RELATED_CONCEPT )
					->withValue( Id::Q_DUCTILITY )
			)
			->withStatement(
				NewStatement::forProperty( Id::P_RELATED_CONCEPT )
					->withValue( Id::Q_HARDNESS )
			)
//			->withStatement(
//				NewStatement::forProperty(Id::P_Translation)
//				->withValue(Id::S_schwierig)
//			)
			->build();
	}

}
