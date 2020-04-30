<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\MediaWiki\Api\EditFormElementsRequest;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\EditFormElementsRequest
 *
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequestTest extends TestCase {

	public function testReturnsChangeOpThatChangesFormElements() {
		$formId = new FormId( 'L1-F1' );
		$colorWithoutU = new Term( 'en', 'color' );
		$colorWithU = new Term( 'en', 'colour' );
		$featureOne = new ItemId( 'Q1' );
		$featureTwo = new ItemId( 'Q2' );

		$form = new Form( $formId, new TermList( [ $colorWithoutU ] ), [ $featureOne ] );

		$request = new EditFormElementsRequest(
			new FormId( 'L1-F1' ),
			new ChangeOpFormEdit( [
				new ChangeOps( new ChangeOpRepresentation( $colorWithU ) ),
				new ChangeOpGrammaticalFeatures( [ $featureTwo ] )
			] ),
			1234
		);

		$changeOp = $request->getChangeOp();

		$changeOp->apply( $form );

		$this->assertEquals( $colorWithU, $form->getRepresentations()->getByLanguage( 'en' ) );
		$this->assertEquals( [ $featureTwo ], $form->getGrammaticalFeatures() );
	}

	public function testGetBaseRevId() {
		$request = new EditFormElementsRequest(
			new FormId( 'L1-F1' ),
			new ChangeOpFormEdit( [] ),
			1234
		);

		$this->assertSame( 1234, $request->getBaseRevId() );
	}

}
