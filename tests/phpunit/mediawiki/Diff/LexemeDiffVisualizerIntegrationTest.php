<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use RequestContext;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Diff\ChangeFormDiffOp;
use Wikibase\Lexeme\Domain\Diff\LexemeDiff;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeIntegrationTestCase;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\WikibaseRepo;

/**
 * Covers entity-diff-visualizer-callback in WikibaseLexeme.entitytypes.php
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeDiffVisualizerIntegrationTest extends WikibaseLexemeIntegrationTestCase {

	private const LANGUAGE_TRANSLATION_HOOK_NAME = 'LanguageGetTranslatedLanguageNames';

	protected function setUp(): void {
		parent::setUp();

		// non-EmptyBagOStuff cache needed for the CachingPrefetchingTermLookup for items
		$this->setService( 'LocalServerObjectCache', new \HashBagOStuff() );
	}

	public function testAddedStatementsWithLexemesAsTargetDisplayLemma() {

		$diffVisualizer = $this->newDiffVisualizer();

		$l1 = new Lexeme( new LexemeId( 'L1' ) );
		$p1 = new Property( new NumericPropertyId( 'P1' ), null, 'wikibase-lexeme' );

		$l1->setLanguage( new ItemId( 'Q1' ) );
		$l1->setLemmas( new TermList( [
			new Term( 'en', 'foo' ) ]
		) );
		$l1->setLexicalCategory( new ItemId( 'Q1' ) );

		$store = $this->getEntityStore();
		$store->saveEntity( $l1, self::class, $this->getTestUser()->getUser() );
		$store->saveEntity( $p1, self::class, $this->getTestUser()->getUser() );

		$addedStatement = new Statement( new PropertyValueSnak( $p1->getId(),
			new EntityIdValue( $l1->getId() ) ), null, null, 's1' );

		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'claim' => new Diff(
					[ 's1' => new DiffOpAdd( $addedStatement ) ],
					true
				),
			] ),
			new Diff(),
			'entity'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'ins' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'foo' )
					) )
				)
			)
		) ) );
	}

	public function testChangedLexicalCategoryItemsAreDisplayedAsLinks() {
		$this->saveItem( 'Q2', 'noun' );
		$this->saveItem( 'Q3', 'verb' );

		$diffVisualizer = $this->newDiffVisualizer();

		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'lexicalCategory' => new Diff(
					[ 'id' => new DiffOpChange( new ItemId( 'Q2' ), new ItemId( 'Q3' ) ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'del' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'noun' ) ) )
				)
			)
		) ) );
		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'ins' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'verb' ) ) )
				)
			)
		) ) );
	}

	public function testChangedLexicalCategoryItemsUseLabelsFromLanguageFallback() {
		$this->setUserLang( 'de' );

		$translatedLanguageName = 'ENGLISCH'; // name of the English language in German
		$this->simulateLanguageTranslation( $translatedLanguageName );

		$this->saveItem( 'Q2', 'noun' );
		$this->saveItem( 'Q3', 'verb' );

		$diffVisualizer = $this->newDiffVisualizer();

		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'lexicalCategory' => new Diff(
					[ 'id' => new DiffOpChange( new ItemId( 'Q2' ), new ItemId( 'Q3' ) ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'del' ),
					havingChild(
						havingTextContents( 'noun' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( "\u{00A0}" . $translatedLanguageName )
					) )
				)
			)
		) ) );
		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'ins' ),
					havingChild(
						havingTextContents( 'verb' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( "\u{00A0}" . $translatedLanguageName )
					) )
				)
			)
		) ) );
	}

	public function testChangedLanguageItemsAreDisplayedAsLinks() {
		$this->saveItem( 'Q321', 'goat language' );
		$this->saveItem( 'Q5', 'cat language' );

		$diffVisualizer = $this->newDiffVisualizer();

		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'language' => new Diff(
					[ 'id' => new DiffOpChange( new ItemId( 'Q321' ), new ItemId( 'Q5' ) ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'del' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )
						->andAlso( havingTextContents( 'goat language' ) ) )
				)
			)
		) ) );
		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'ins' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )
						->andAlso( havingTextContents( 'cat language' ) ) )
				)
			)
		) ) );
	}

	public function testChangedLanguageItemsUseLabelsFromLanguageFallback() {
		$this->setUserLang( 'de' );

		$translatedLanguageName = 'ENGLISCH'; // name of the English language in German
		$this->simulateLanguageTranslation( $translatedLanguageName );

		$this->saveItem( 'Q4', 'goat language' );
		$this->saveItem( 'Q5', 'cat language' );

		$diffVisualizer = $this->newDiffVisualizer();

		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'language' => new Diff(
					[ 'id' => new DiffOpChange( new ItemId( 'Q4' ), new ItemId( 'Q5' ) ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'del' ),
					havingChild(
						havingTextContents( 'goat language' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( "\u{00A0}" . $translatedLanguageName )
					) )
				)
			)
		) ) );
		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'ins' ),
					havingChild(
						havingTextContents( 'cat language' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( "\u{00A0}" . $translatedLanguageName )
					) )
				)
			)
		) ) );
	}

	public function testGrammaticalFeatureItemsAreDisplayedAsLinks() {
		$this->saveItem( 'Q234', 'singular' );
		$this->saveItem( 'Q345', 'plural' );

		$diffVisualizer = $this->newDiffVisualizer();

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( new EntityContentDiff(
			new LexemeDiff( [
				'forms' => new ChangeFormDiffOp(
					new FormId( 'L1-F1' ),
					new Diff( [
						'grammaticalFeatures' => new Diff( [
							new DiffOpRemove( new ItemId( 'Q345' ) ),
							new DiffOpAdd( new ItemId( 'Q234' ) ),
						] ),
					] )
				)
			] ),
			new Diff(),
			'lexeme'
		) );

		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'del' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'plural' ) ) )
				)
			)
		) ) );
		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'ins' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'singular' ) ) )
				)
			)
		) ) );
	}

	public function testChangedGrammaticalFeatureItemsUseLabelsFromLanguageFallback() {
		$this->setUserLang( 'de' );

		$translatedLanguageName = 'ENGLISCH'; // name of the English language in German
		$this->simulateLanguageTranslation( $translatedLanguageName );

		$this->saveItem( 'Q123', 'singular' );
		$this->saveItem( 'Q321', 'plural' );

		$diffVisualizer = $this->newDiffVisualizer();

		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'forms' => new ChangeFormDiffOp(
					new FormId( 'L1-F1' ),
					new Diff( [
						'grammaticalFeatures' => new Diff( [
							new DiffOpRemove( new ItemId( 'Q321' ) ),
							new DiffOpAdd( new ItemId( 'Q123' ) ),
						] ),
					] )
				)
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'del' ),
					havingChild(
						havingTextContents( 'plural' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( "\u{00A0}" . $translatedLanguageName )
					) )
				)
			)
		) ) );
		$this->assertThatHamcrest( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'ins' ),
					havingChild(
						havingTextContents( 'singular' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( "\u{00A0}" . $translatedLanguageName )
					) )
				)
			)
		) ) );
	}

	public function testAddedStatementsOnFormsTargettingFormsAreDisplayedAsLinks() {
		$diffVisualizer = $this->newDiffVisualizer();

		$l1 = new Lexeme(
			new LexemeId( 'L1' ), new TermList( [ new Term( 'en', 'LemmaLem' ) ] ),
			new ItemId( 'Q1' ), new ItemId( 'Q1' )
		);
		$f1 = new BlankForm();
		$f1->getRepresentations()->setTextForLanguage( 'de', 'baz' );
		$f1->setGrammaticalFeatures( [ new ItemId( 'Q1' ) ] );
		$l1->addOrUpdateForm( $f1 );

		$p1 = new Property( new NumericPropertyId( 'P1' ), null, 'wikibase-form' );

		$store = $this->getEntityStore();
		$store->saveEntity( $l1, self::class, $this->getTestUser()->getUser() );
		$store->saveEntity( $p1, self::class, $this->getTestUser()->getUser() );

		$addedStatement = new Statement( new PropertyValueSnak( $p1->getId(),
			new EntityIdValue( $f1->getId() ) ), null, null, 's1' );

		$diff = new EntityContentDiff( new LexemeDiff( [
			'forms' => new ChangeFormDiffOp(
				$f1->getId(),
				new Diff( [
					'claim' => new Diff( [ 's1' => new DiffOpAdd( $addedStatement ) ], true ),
				], true )
			),
		] ), new Diff(), 'lexeme' );

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		$this->assertThatHamcrest(
			$diffHtml,
			is(
				htmlPiece(
					havingChild(
						both( withTagName( 'ins' ) )->andAlso(
							havingChild(
								both( withTagName( 'a' ) )->andAlso(
									havingTextContents( 'baz' )
								)
							)
						)
					)
				)
			)
		);
	}

	/**
	 * Make the Language class translate "English" in a certain way, e.g. in line with setUserLang()
	 *
	 * @param string $languageName Translation to use for the English language
	 */
	private function simulateLanguageTranslation( $languageName ) {
		// mimics CLDR behavior
		$this->setTemporaryHook(
			self::LANGUAGE_TRANSLATION_HOOK_NAME,
			static function ( &$names, $inLanguage ) use ( $languageName ) {
				$names['en'] = $languageName;
			}
		);
	}

	private function saveItem( $id, $label ) {
		$item = new Item(
			new ItemId( $id ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', $label ),
				] )
			)
		);

		$this->saveEntity( $item );
	}

	private function newDiffVisualizer() {
		return WikibaseRepo::getEntityDiffVisualizerFactory()
			->newEntityDiffVisualizer( 'lexeme', RequestContext::getMain() );
	}

}
