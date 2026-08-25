<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataAccess\Store;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\DataAccess\CrudEditSummaryAdapter;
use Wikibase\Lexeme\DataAccess\Store\EntityUpdaterLexemeCreator;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata as CrudEditMetadata;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\EntityUpdaterLexemeCreator
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterLexemeCreatorTest extends MediaWikiUnitTestCase {

	public function testCreate(): void {
		$lexemeTemplate = NewLexeme::create()
			->withLemma( 'en', 'potato' )
			->withLanguage( 'Q1' )
			->withLexicalCategory( 'Q2' );
		$lexemeToCreate = $lexemeTemplate->build();
		$createdLexeme = $lexemeTemplate->withId( 'L1' )->build();

		$tags = [ 'some tag' ];
		$isBot = true;
		$comment = 'user comment';
		$editMetadata = new EditMetadata( $tags, $isBot, $comment, EditSummaryAction::CREATE_LEXEME );
		$revisionId = 123;
		$lastModified = '20250101120000';

		$entityUpdater = $this->createMock( EntityUpdater::class );
		$entityUpdater->expects( $this->once() )
			->method( 'create' )
			->with(
				$lexemeToCreate,
				new CrudEditMetadata(
					$tags,
					$isBot,
					new CrudEditSummaryAdapter( EditSummaryAction::CREATE_LEXEME, $comment ),
				),
			)
			->willReturn( new EntityRevision( $createdLexeme, $revisionId, $lastModified ) );

		$lexemeRevision = ( new EntityUpdaterLexemeCreator( $entityUpdater ) )
			->create( $lexemeToCreate, $editMetadata );

		$this->assertEquals( $createdLexeme->getId(), $lexemeRevision->lexeme->id );
		$this->assertSame( $revisionId, $lexemeRevision->revisionId );
		$this->assertSame( $lastModified, $lexemeRevision->lastModified );
	}

}
