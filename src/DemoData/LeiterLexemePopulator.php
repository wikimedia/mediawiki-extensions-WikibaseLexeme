<?php

namespace Wikibase\Lexeme\DemoData;

use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Repo\Tests\NewStatement;

class LeiterLexemePopulator {

	public function populate( Lexeme $lexeme ) {
		$leiterForm = $this->buildLeiterForm();
		$leitersForm = $this->buildLeitersForm();
		$leiterinForm = $this->buildLeiterinForm();

		$leaderSense = $this->buildLeaderSense();
		$electricalConductorSense = $this->buildElectricalConductorSense();

		$lexeme->setForms( [ $leiterForm, $leitersForm, $leiterinForm ] );
		$lexeme->setSenses( [ $leaderSense, $electricalConductorSense ] );
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Form
	 */
	private function buildLeiterForm() {
		return NewForm::havingId( 'F1' )
			->andRepresentation( 'de', 'Leiter' )
			->andGrammaticalFeature( Id::Q_NOMINATIVE )
			->andGrammaticalFeature( Id::Q_SINGULAR )
			->andStatement(
				NewStatement::forProperty( Id::P_IPA_PRONUNCIATION )
					->withValue( '/.../' )
					->withSomeGuid()
			)->andStatement(
				NewStatement::forProperty( Id::P_SYLLABIFICATION )
					->withValue( 'Lei-ter' )
					->withSomeGuid()
			)->build();
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Form
	 */
	private function buildLeitersForm() {
		return NewForm::havingId( 'F2' )
			->andRepresentation( 'de', 'Leiters' )
			->andGrammaticalFeature( Id::Q_GENITIVE )
			->andGrammaticalFeature( Id::Q_SINGULAR )
			->build();
	}

	private function buildLeiterinForm() {
		return NewForm::havingId( 'F3' )
			->andRepresentation( 'de', 'Leiterin' )
			->andGrammaticalFeature( Id::Q_NOMINATIVE )
			->andGrammaticalFeature( Id::Q_SINGULAR )
			->andGrammaticalFeature( Id::Q_FEMALE )
			->andStatement(
				NewStatement::forProperty( Id::P_REFERS_TO_SENSE )
					->withValue( Id::LS_LEADER )
					->withSomeGuid()
			)
			->build();
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildLeaderSense() {
		return NewSense::havingId( 'S1' )
			->withGloss( 'de', 'Führungsperson' )
			->withGloss( 'en', 'leader' )
			->build();
	}

	/**
	 * @return \Wikibase\Lexeme\DataModel\Sense
	 */
	private function buildElectricalConductorSense() {
		return NewSense::havingId( 'S2' )
			->withGloss( 'de', 'elektrischer Leiter' )
			->withGloss( 'en', 'electrical conductor' )
			->build();
	}

}
