<?php

namespace Wikibase\Lexeme\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Hooks;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Services\Diff\ChangeFormDiffOp;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiff;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\WikibaseRepo;

/**
 * Covers entity-diff-visualizer-callback in WikibaseLexeme.entitytypes.php
 *
 * @license GPL-2.0-or-later
 *
 * @group Database
 * @group medium
 */
class LexemeDiffVisualizerIntegrationTest extends \MediaWikiLangTestCase {

	const LANGUAGE_TRANSLATION_HOOK_NAME = 'LanguageGetTranslatedLanguageNames';
	private $hookHandlers = [];

	/**
	 * Backs up Hook::$handlers to be reset after tearDown
	 *
	 * @throws \MWException
	 */
	public function setUp() {
		parent::setUp();

		$this->hookHandlers = $this->getHookHandlersProperty()->getValue();
	}

	public function tearDown() {
		parent::tearDown();

		$this->getHookHandlersProperty()->setValue( $this->hookHandlers );
		$this->clearLanguageNameCache();
	}

	public function testChangedLexicalCategoryItemsAreDisplayedAsLinks() {
		$this->saveItem( 'Q2', 'noun' );
		$this->saveItem( 'Q3', 'verb' );

		$diffVisualizer = $this->newDiffVisualizer();

		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'lexicalCategory' => new Diff(
					[ 'id' => new DiffOpChange( 'Q2', 'Q3' ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'del' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'noun' ) ) )
				)
			)
		) ) );
		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'ins' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'verb' ) ) )
				)
			)
		) ) );
		$this->assertTrue( true, 'Stop the test being marked risky' );
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
					[ 'id' => new DiffOpChange( 'Q2', 'Q3' ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'del' ),
					havingChild(
						havingTextContents( 'noun' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( $translatedLanguageName )
					) )
				)
			)
		) ) );
		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'ins' ),
					havingChild(
						havingTextContents( 'verb' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( $translatedLanguageName )
					) )
				)
			)
		) ) );

		$this->assertTrue( true, 'Stop the test being marked risky' );
	}

	public function testChangedLanguageItemsAreDisplayedAsLinks() {
		$this->saveItem( 'Q4', 'goat language' );
		$this->saveItem( 'Q5', 'cat language' );

		$diffVisualizer = $this->newDiffVisualizer();

		$diff = new EntityContentDiff(
			new LexemeDiff( [
				'language' => new Diff(
					[ 'id' => new DiffOpChange( 'Q4', 'Q5' ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'del' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )
						->andAlso( havingTextContents( 'goat language' ) ) )
				)
			)
		) ) );
		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'ins' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )
						->andAlso( havingTextContents( 'cat language' ) ) )
				)
			)
		) ) );
		$this->assertTrue( true, 'Stop the test being marked risky' );
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
					[ 'id' => new DiffOpChange( 'Q4', 'Q5' ) ],
					true
				),
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'del' ),
					havingChild(
						havingTextContents( 'goat language' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( $translatedLanguageName )
					) )
				)
			)
		) ) );
		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'ins' ),
					havingChild(
						havingTextContents( 'cat language' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( $translatedLanguageName )
					) )
				)
			)
		) ) );

		$this->assertTrue( true, 'Stop the test being marked risky' );
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
							new DiffOpRemove( 'Q345' ),
							new DiffOpAdd( 'Q234' ),
						] ),
					] )
				)
			] ),
			new Diff(),
			'lexeme'
		) );

		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'del' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'plural' ) ) )
				)
			)
		) ) );
		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				both( withTagName( 'ins' ) )->andAlso(
					havingChild( both( withTagName( 'a' ) )->andAlso( havingTextContents( 'singular' ) ) )
				)
			)
		) ) );

		$this->assertTrue( true, 'Stop the test being marked risky' );
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
							new DiffOpRemove( 'Q321' ),
							new DiffOpAdd( 'Q123' ),
						] ),
					] )
				)
			] ),
			new Diff(),
			'lexeme'
		);

		$diffHtml = $diffVisualizer->visualizeEntityContentDiff( $diff );

		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'del' ),
					havingChild(
						havingTextContents( 'plural' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( $translatedLanguageName )
					) )
				)
			)
		) ) );
		assertThat( $diffHtml, is( htmlPiece(
			havingChild(
				allOf(
					withTagName( 'ins' ),
					havingChild(
						havingTextContents( 'singular' )
					),
					havingChild( both(
						tagMatchingOutline( '<sup class="wb-language-fallback-indicator"/>' )
					)->andAlso(
						havingTextContents( $translatedLanguageName )
					) )
				)
			)
		) ) );

		$this->assertTrue( true, 'Stop the test being marked risky' );
	}

	/**
	 * Make the Language class translate "English" in a certain way, e.g. in line with setUserLang()
	 *
	 * @param string $languageName Translation to use for the English language
	 */
	private function simulateLanguageTranslation( $languageName ) {
		// mimics CLDR behavior
		Hooks::register(
			self::LANGUAGE_TRANSLATION_HOOK_NAME,
			function ( &$names, $inLanguage ) use ( $languageName ) {
				$names['en'] = $languageName;
			}
		);
	}

	private function getHookHandlersProperty() {
		$handlers = ( new \ReflectionClass( \Hooks::class ) )->getProperty( 'handlers' );
		$handlers->setAccessible( true );

		return $handlers;
	}

	private function clearLanguageNameCache() {
		$languageClass = new \ReflectionClass( \Language::class );
		$cacheProperty = $languageClass->getProperty( 'languageNameCache' );
		$cacheProperty->setAccessible( true );
		$cacheProperty->setValue( null );
	}

	private function saveItem( $id, $label ) {
		$lexeme = new Item(
			new ItemId( $id ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', $label ),
				] )
			)
		);

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$store->saveEntity( $lexeme, self::class, $this->getMock( \User::class ) );
	}

	private function newDiffVisualizer() {
		return WikibaseRepo::getDefaultInstance()->getEntityDiffVisualizerFactory( new \RequestContext() )
			->newEntityDiffVisualizer( 'lexeme' );
	}

}
