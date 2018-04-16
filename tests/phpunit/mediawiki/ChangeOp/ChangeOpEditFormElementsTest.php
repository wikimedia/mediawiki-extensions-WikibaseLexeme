<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpEditFormElements;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Repo\Tests\NewItem;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpEditFormElements
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpEditFormElementsTest extends TestCase {

	use PHPUnit4And6Compat;

	public function test_validateFailsIfProvidedEntityIsNotAForm() {
		$changeOp = new ChangeOpEditFormElements( new TermList(), [] );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOp->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validatePassesIfProvidedEntityIsAForm() {
		$changeOp = new ChangeOpEditFormElements( new TermList(), [] );

		$result = $changeOp->validate( NewForm::any()->build() );

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotAForm() {
		$changeOp = new ChangeOpEditFormElements( new TermList(), [] );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOp->apply( NewItem::withId( 'Q1' )->build() );
	}

	// TODO: Probably might be preferred to have this split to add/change/remove cases?
	public function test_applyChangesFormElements() {
		$representations = new TermList( [ new Term( 'en', 'goat' ) ] );
		$changeOp = new ChangeOpEditFormElements( $representations, [ new ItemId( 'Q1000' ) ] );
		$form = NewForm::havingRepresentation( 'en', 'cat' )
			->andGrammaticalFeature( 'Q1' )
			->build();

		$changeOp->apply( $form );

		$this->assertEquals( $representations, $form->getRepresentations() );
		$this->assertEquals( [ new ItemId( 'Q1000' ) ], $form->getGrammaticalFeatures() );
	}

	// TODO: Test the summary is set as specified, once specified
}
