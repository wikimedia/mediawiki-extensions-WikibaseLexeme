<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use Action;
use Closure;
use FauxRequest;
use IContextSource;
use Language;
use Page;
use PHPUnit_Framework_TestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lexeme\Content\LexemeHandler;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\Lexeme\Content\LexemeHandler
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LexemeHandlerTest extends PHPUnit_Framework_TestCase {

	private function getMockWithoutConstructor( $className ) {
		return $this->getMockBuilder( $className )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newLexemeHandler() {
		$labelLookupFactory = $this->getMockWithoutConstructor(
			LanguageFallbackLabelDescriptionLookupFactory::class
		);
		$labelLookupFactory->expects( $this->any() )
			->method( 'newLabelDescriptionLookup' )
			->will( $this->returnValue( $this->getMock( LabelDescriptionLookup::class ) ) );

		return new LexemeHandler(
			$this->getMock( EntityPerPage::class ),
			$this->getMock( TermIndex::class ),
			$this->getMockWithoutConstructor( EntityContentDataCodec::class ),
			$this->getMockWithoutConstructor( EntityConstraintProvider::class ),
			$this->getMock( ValidatorErrorLocalizer::class ),
			$this->getMock( EntityIdParser::class ),
			$this->getMock( EntityIdLookup::class ),
			$labelLookupFactory
		);
	}

	public function testGetActionOverrides() {
		$lexemeHandler = $this->newLexemeHandler();
		$overrides = $lexemeHandler->getActionOverrides();

		$this->assertSame( [ 'history', 'view', 'edit', 'submit' ], array_keys( $overrides ) );

		$this->assertActionOverride( $overrides['history'] );
		$this->assertActionOverride( $overrides['view'] );
		$this->assertActionOverride( $overrides['edit'] );
		$this->assertActionOverride( $overrides['submit'] );
	}

	private function assertActionOverride( $override ) {
		if ( $override instanceof Closure ) {
			$context = $this->getMock( IContextSource::class );
			$context->expects( $this->any() )
				->method( 'getLanguage' )
				->will( $this->returnValue( $this->getMockWithoutConstructor( Language::class ) ) );

			$action = $override( $this->getMock( Page::class ), $context );
			$this->assertInstanceOf( Action::class, $action );
		} else {
			$this->assertTrue( is_subclass_of( $override, Action::class ) );
		}
	}

	public function testMakeEmptyEntity() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertTrue(
			$lexemeHandler->makeEmptyEntity()->equals( new Lexeme() )
		);
	}

	public function testMakeEntityId() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertTrue(
			$lexemeHandler->makeEntityId( 'L1' )->equals( new LexemeId( 'L1' ) )
		);
	}

	public function testGetEntityType() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertSame( Lexeme::ENTITY_TYPE, $lexemeHandler->getEntityType() );
	}

	public function testShowMissingEntity() {
		$lexemeHandler = $this->newLexemeHandler();

		$title = Title::makeTitle( 112, 'L11' );
		$context = new RequestContext( new FauxRequest() );
		$context->setTitle( $title );

		$lexemeHandler->showMissingEntity( $title, $context );

		$html = $context->getOutput()->getHTML();
		$this->assertContains( 'noarticletext', $html );
	}

	public function testAllowAutomaticIds() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertTrue( $lexemeHandler->allowAutomaticIds() );
	}

	public function testCanCreateWithCustomId() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertFalse( $lexemeHandler->canCreateWithCustomId( new LexemeId( 'L1' ) ) );
	}

}
