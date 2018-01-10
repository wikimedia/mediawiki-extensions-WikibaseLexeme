<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Api\EditFormElementsRequest;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @covers \Wikibase\Lexeme\Api\EditFormElementsRequest
 *
 * @license GPL-2.0+
 */
class EditFormElementsRequestTest extends \PHPUnit_Framework_TestCase {

	public function testReturnsChangeOpThatChangesFormElements() {
		$formId = new FormId( 'L1-F1' );
		$colorWithoutU = new Term( 'en', 'color' );
		$colorWithU = new Term( 'en', 'colour' );
		$featureOne = new ItemId( 'Q1' );
		$featureTwo = new ItemId( 'Q2' );

		$form = new Form( $formId, new TermList( [ $colorWithoutU ] ), [ $featureOne ] );

		$request = new EditFormElementsRequest(
			new FormId( 'L1-F1' ),
			new TermList( [ $colorWithU ] ),
			[ $featureTwo ]
		);

		$changeOp = $request->getChangeOp();

		$changeOp->apply( $form );

		$this->assertEquals( $colorWithU, $form->getRepresentations()->getByLanguage( 'en' ) );
		$this->assertEquals( [ $featureTwo ], $form->getGrammaticalFeatures() );
	}

}
