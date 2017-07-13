<?php

namespace Wikibase\Lexeme\DemoData;

use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;

class DefaultPopulator {

	public function populate( Lexeme $lexeme ) {
		$grammaticalFeatures1 = [ new ItemId( 'Q2' ) ];
		$grammaticalFeatures2 = [ new ItemId( 'Q2' ), new ItemId( 'Q3' ) ];
		$statements1 = new StatementList(
			[
				new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ), null, null, 'guid1' )
			]
		);
		$statements2 = new StatementList(
			[
				new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ), null, null, 'guid2' ),
				new Statement(
					new PropertyValueSnak(
						new PropertyId( 'P3' ),
						new StringValue( 'asd' )
					),
					null,
					null,
					'guid3'
				),
			]
		);

		$forms = [
			new Form(
				new FormId( 'F1' ),
				new TermList( [ new Term( 'en', 'A' ) ] ),
				[]
			),
			new Form(
				new FormId( 'F2' ),
				new TermList( [ new Term( 'en', 'B' ) ] ),
				$grammaticalFeatures1,
				$statements1
			),
			new Form(
				new FormId( 'F3' ),
				new TermList( [ new Term( 'en', 'C' ) ] ),
				$grammaticalFeatures2,
				$statements2
			),
		];

		$lexeme->setForms( $forms );

		$senses = [
			new Sense(
				new SenseId( 'S1' ),
				new TermList( [
								  new Term(
									  'en',
									  'A mammal, Capra aegagrus hircus, and similar species of the genus Capra.'
								  ),
								  new Term(
									  'fr',
									  'Un mammale, Capra aegagruse hircuse, et similare species de un genuse Capra.'
								  ),
							  ] ),
				new StatementList()
			),
			new Sense(
				new SenseId( 'S2' ),
				new TermList( [ new Term( 'en', 'A scapegoat.' ) ] ),
				new StatementList( [
									   new Statement(
										   new PropertyValueSnak(
											   new PropertyId( 'P900' ),
											   new StringValue( 'informal' )
										   ),
										   null,
										   null,
										   'guid900'
									   ),
								   ] )
			)
		];

		$lexeme->setSenses( $senses );
	}

}
