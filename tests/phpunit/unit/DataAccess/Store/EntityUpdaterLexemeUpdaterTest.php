<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataAccess\Store;

use Exception;
use InvalidArgumentException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lexeme\DataAccess\CrudEditSummaryAdapter;
use Wikibase\Lexeme\DataAccess\Store\EntityUpdaterLexemeUpdater;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Lexeme\Domain\Model\Exceptions\RateLimitReached;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata as CrudEditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\RateLimitReached as CrudRateLimitReached;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\ResourceTooLargeException as CrudResourceTooLargeException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\TempAccountCreationLimitReached as CrudTempAccountException;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Statement;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\EntityUpdaterLexemeUpdater
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterLexemeUpdaterTest extends MediaWikiUnitTestCase {

	public function testCreate(): void {
		$lexemeId = new LexemeId( 'L1' );
		$lemma = new Lemma( 'en', 'potato' );
		$language = new ItemId( 'Q1' );
		$lexicalCategory = new ItemId( 'Q2' );

		$lexemeTemplate = NewLexeme::create()
			->withLemma( $lemma->languageCode, $lemma->text )
			->withLanguage( $language )
			->withLexicalCategory( $lexicalCategory )
			->withStatement( new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) );
		$lexemeToCreate = $lexemeTemplate->build();
		$createdLexeme = $lexemeTemplate->withId( $lexemeId )->build();

		$tags = [ 'some tag' ];
		$isBot = true;
		$comment = 'user comment';
		$editMetadata = new EditMetadata( $tags, $isBot, $comment, EditSummaryAction::CREATE_LEXEME );
		$revisionId = 123;
		$lastModified = '20250101120000';
		$readModelStatement = $this->createStub( Statement::class );

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

		$statementReadModelConverter = $this->createStub( StatementReadModelConverter::class );
		$statementReadModelConverter->method( 'convert' )->willReturn( $readModelStatement );

		$lexemeRevision = ( new EntityUpdaterLexemeUpdater(
			$entityUpdater,
			$statementReadModelConverter,
		) )->create( $lexemeToCreate, $editMetadata );

		$this->assertEquals(
			new Lexeme(
				$lexemeId,
				new Lemmas( $lemma ),
				$lexicalCategory,
				$language,
				new StatementList( $readModelStatement ),
				new Forms(),
				new Senses(),
			),
			$lexemeRevision->lexeme,
		);
		$this->assertSame( $revisionId, $lexemeRevision->revisionId );
		$this->assertSame( $lastModified, $lexemeRevision->lastModified );
	}

	public function testCreateWithId_throws(): void {
		$lexemeCreator = new EntityUpdaterLexemeUpdater(
			$this->createNoOpMock( EntityUpdater::class ),
			$this->createStub( StatementReadModelConverter::class ),
		);

		$this->expectException( InvalidArgumentException::class );

		$lexemeCreator->create(
			NewLexeme::havingId( 'L1' )->build(),
			new EditMetadata( [], false, 'user comment', EditSummaryAction::CREATE_LEXEME ),
		);
	}

	public function testUpdate(): void {
		$lexemeId = new LexemeId( 'L1' );
		$lemma = new Lemma( 'en', 'potato' );
		$language = new ItemId( 'Q1' );
		$lexicalCategory = new ItemId( 'Q2' );

		$lexemeToUpdate = NewLexeme::havingId( $lexemeId )
			->withLemma( $lemma->languageCode, $lemma->text )
			->withLanguage( $language )
			->withLexicalCategory( $lexicalCategory )
			->withStatement( new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) )
			->build();

		$tags = [ 'some tag' ];
		$isBot = true;
		$comment = 'user comment';
		$editMetadata = new EditMetadata( $tags, $isBot, $comment, EditSummaryAction::CREATE_LEXEME );
		$revisionId = 123;
		$lastModified = '20250101120000';
		$readModelStatement = $this->createStub( Statement::class );

		$entityUpdater = $this->createMock( EntityUpdater::class );
		$entityUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$lexemeToUpdate,
				new CrudEditMetadata(
					$tags,
					$isBot,
					new CrudEditSummaryAdapter( EditSummaryAction::CREATE_LEXEME, $comment ),
				),
			)
			->willReturn( new EntityRevision( $lexemeToUpdate, $revisionId, $lastModified ) );

		$statementReadModelConverter = $this->createStub( StatementReadModelConverter::class );
		$statementReadModelConverter->method( 'convert' )->willReturn( $readModelStatement );

		$lexemeRevision = ( new EntityUpdaterLexemeUpdater(
			$entityUpdater,
			$statementReadModelConverter,
		) )->update( $lexemeToUpdate, $editMetadata );

		$this->assertEquals(
			new Lexeme(
				$lexemeId,
				new Lemmas( $lemma ),
				$lexicalCategory,
				$language,
				new StatementList( $readModelStatement ),
				new Forms(),
				new Senses(),
			),
			$lexemeRevision->lexeme,
		);
		$this->assertSame( $revisionId, $lexemeRevision->revisionId );
		$this->assertSame( $lastModified, $lexemeRevision->lastModified );
	}

	public function testUpdateWithoutId_throws(): void {
		$lexemeUpdater = new EntityUpdaterLexemeUpdater(
			$this->createNoOpMock( EntityUpdater::class ),
			$this->createStub( StatementReadModelConverter::class ),
		);

		$this->expectException( InvalidArgumentException::class );

		$lexemeUpdater->update(
			NewLexeme::create()->build(),
			new EditMetadata( [], false, 'user comment', EditSummaryAction::CREATE_LEXEME ),
		);
	}

	/**
	 * @dataProvider exceptionProvider
	 */
	public function testCreateGivenCrudException_throwsLexemeException(
		Exception $crudException,
		Exception $expectedException
	): void {
		$entityUpdater = $this->createStub( EntityUpdater::class );
		$entityUpdater->method( 'create' )->willThrowException( $crudException );
		$lexemeUpdater = new EntityUpdaterLexemeUpdater(
			$entityUpdater,
			$this->createStub( StatementReadModelConverter::class ),
		);
		try {
			$lexemeUpdater->create(
				NewLexeme::create()->build(),
				new EditMetadata( [], false, 'user comment', EditSummaryAction::CREATE_LEXEME ),
			);
			$this->fail( 'expected Exception not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	/**
	 * @dataProvider exceptionProvider
	 */
	public function testUpdateGivenCrudException_throwsLexemeException(
		Exception $crudException,
		Exception $expectedException
	): void {
		$entityUpdater = $this->createStub( EntityUpdater::class );
		$entityUpdater->method( 'update' )->willThrowException( $crudException );
		$lexemeUpdater = new EntityUpdaterLexemeUpdater(
			$entityUpdater,
			$this->createStub( StatementReadModelConverter::class ),
		);
		try {
			$lexemeUpdater->update(
				NewLexeme::havingId( 'L1' )->build(),
				new EditMetadata( [], false, 'user comment', EditSummaryAction::CREATE_LEXEME ),
			);
			$this->fail( 'expected Exception not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function exceptionProvider(): iterable {
		yield 'rate limit reached' => [
			new CrudRateLimitReached(),
			new RateLimitReached(),
		];

		yield 'resource too large' => [
			new CrudResourceTooLargeException( 42 ),
			new ResourceTooLargeException( 42 ),
		];

		yield 'temp account creation limit reached' => [
			new CrudTempAccountException(),
			new TempAccountCreationLimitReached(),
		];
	}

}
