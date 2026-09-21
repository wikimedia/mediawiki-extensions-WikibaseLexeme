<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataAccess\Store;

use Exception;
use InvalidArgumentException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\DataAccess\CrudEditSummaryAdapter;
use Wikibase\Lexeme\DataAccess\Store\EntityUpdaterLexemeUpdater;
use Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter;
use Wikibase\Lexeme\Domain\Model\CreateLexemeEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Exceptions\EditPrevented;
use Wikibase\Lexeme\Domain\Model\Exceptions\RateLimitReached;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata as CrudEditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\EditPrevented as CrudEditPrevented;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\RateLimitReached as CrudRateLimitReached;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\ResourceTooLargeException as CrudResourceTooLargeException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\TempAccountCreationLimitReached as CrudTempAccountException;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;

/**
 * @covers \Wikibase\Lexeme\DataAccess\Store\EntityUpdaterLexemeUpdater
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterLexemeUpdaterTest extends MediaWikiUnitTestCase {

	public function testCreate(): void {
		$lexemeId = new LexemeId( 'L1' );

		$lexemeTemplate = NewLexeme::create()
			->withLemma( 'en', 'potato' )
			->withLanguage( new ItemId( 'Q1' ) )
			->withLexicalCategory( new ItemId( 'Q2' ) );
		$lexemeToCreate = $lexemeTemplate->build();
		$createdLexeme = $lexemeTemplate->withId( $lexemeId )->build();

		$tags = [ 'some tag' ];
		$isBot = true;
		$comment = 'user comment';
		$editMetadata = new EditMetadata( $tags, $isBot, new CreateLexemeEditSummary( $comment ) );
		$revisionId = 123;
		$lastModified = '20250101120000';
		$lexemeReadModel = $this->createStub( Lexeme::class );

		$entityUpdater = $this->createMock( EntityUpdater::class );
		$entityUpdater->expects( $this->once() )
			->method( 'create' )
			->with(
				$lexemeToCreate,
				new CrudEditMetadata(
					$tags,
					$isBot,
					new CrudEditSummaryAdapter( new CreateLexemeEditSummary( $comment ) ),
				),
			)
			->willReturn( new EntityRevision( $createdLexeme, $revisionId, $lastModified ) );

		$lexemeReadModelConverter = $this->createMock( LexemeReadModelConverter::class );
		$lexemeReadModelConverter->expects( $this->once() )
			->method( 'convert' )
			->with( $createdLexeme )
			->willReturn( $lexemeReadModel );

		$lexemeRevision = ( new EntityUpdaterLexemeUpdater(
			$entityUpdater,
			$lexemeReadModelConverter,
			new GuidGenerator(),
		) )->create( $lexemeToCreate, $editMetadata );

		$this->assertSame( $lexemeReadModel, $lexemeRevision->lexeme );
		$this->assertSame( $revisionId, $lexemeRevision->revisionId );
		$this->assertSame( $lastModified, $lexemeRevision->lastModified );
	}

	public function testCreateWithId_throws(): void {
		$lexemeCreator = new EntityUpdaterLexemeUpdater(
			$this->createNoOpMock( EntityUpdater::class ),
			$this->createStub( LexemeReadModelConverter::class ),
			new GuidGenerator(),
		);

		$this->expectException( InvalidArgumentException::class );

		$lexemeCreator->create(
			NewLexeme::havingId( 'L1' )->build(),
			new EditMetadata( [], false, new CreateLexemeEditSummary( 'user comment' ) ),
		);
	}

	public function testUpdate(): void {
		$lexemeToUpdate = NewLexeme::havingId( new LexemeId( 'L1' ) )
			->withLemma( 'en', 'potato' )
			->withLanguage( new ItemId( 'Q1' ) )
			->withLexicalCategory( new ItemId( 'Q2' ) )
			->build();

		$tags = [ 'some tag' ];
		$isBot = true;
		$comment = 'user comment';
		$editMetadata = new EditMetadata( $tags, $isBot, new CreateLexemeEditSummary( $comment ) );
		$revisionId = 123;
		$lastModified = '20250101120000';
		$lexemeReadModel = $this->createStub( Lexeme::class );

		$entityUpdater = $this->createMock( EntityUpdater::class );
		$entityUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$lexemeToUpdate,
				new CrudEditMetadata(
					$tags,
					$isBot,
					new CrudEditSummaryAdapter( new CreateLexemeEditSummary( $comment ) ),
				),
			)
			->willReturn( new EntityRevision( $lexemeToUpdate, $revisionId, $lastModified ) );

		$lexemeReadModelConverter = $this->createMock( LexemeReadModelConverter::class );
		$lexemeReadModelConverter->expects( $this->once() )
			->method( 'convert' )
			->with( $lexemeToUpdate )
			->willReturn( $lexemeReadModel );

		$lexemeRevision = ( new EntityUpdaterLexemeUpdater(
			$entityUpdater,
			$lexemeReadModelConverter,
			new GuidGenerator(),
		) )->update( $lexemeToUpdate, $editMetadata );

		$this->assertSame( $lexemeReadModel, $lexemeRevision->lexeme );
		$this->assertSame( $revisionId, $lexemeRevision->revisionId );
		$this->assertSame( $lastModified, $lexemeRevision->lastModified );
	}

	public function testUpdateGeneratesFormStatementIds(): void {
		$existingStatementId = 'L1-F1$00000000-0000-0000-0000-000000000000';
		$statementWithId = new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P321' ) ) );
		$statementWithId->setGuid( $existingStatementId );

		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andStatement( new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) ) )
					->andStatement( $statementWithId )
			)
			->build();

		$entityUpdater = $this->createStub( EntityUpdater::class );
		$entityUpdater->method( 'update' )->willReturn( new EntityRevision( $lexeme, 123, '20250101120000' ) );

		( new EntityUpdaterLexemeUpdater(
			$entityUpdater,
			$this->createStub( LexemeReadModelConverter::class ),
			new GuidGenerator(),
		) )->update( $lexeme, new EditMetadata( [], false, new CreateLexemeEditSummary( 'user comment' ) ) );

		$formStatements = $lexeme->getForms()->toArray()[0]->getStatements()->toArray();
		$this->assertStringStartsWith( 'L1-F1$', (string)$formStatements[0]->getGuid() );
		$this->assertSame( $existingStatementId, $formStatements[1]->getGuid() );
	}

	public function testUpdateWithoutId_throws(): void {
		$lexemeUpdater = new EntityUpdaterLexemeUpdater(
			$this->createNoOpMock( EntityUpdater::class ),
			$this->createStub( LexemeReadModelConverter::class ),
			new GuidGenerator(),
		);

		$this->expectException( InvalidArgumentException::class );

		$lexemeUpdater->update(
			NewLexeme::create()->build(),
			new EditMetadata( [], false, new CreateLexemeEditSummary( 'user comment' ) ),
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
			$this->createStub( LexemeReadModelConverter::class ),
			new GuidGenerator(),
		);
		try {
			$lexemeUpdater->create(
				NewLexeme::create()->build(),
				new EditMetadata( [], false, new CreateLexemeEditSummary( 'user comment' ) ),
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
			$this->createStub( LexemeReadModelConverter::class ),
			new GuidGenerator(),
		);
		try {
			$lexemeUpdater->update(
				NewLexeme::havingId( 'L1' )->build(),
				new EditMetadata( [], false, new CreateLexemeEditSummary( 'user comment' ) ),
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

		yield 'edit prevented' => [
			new CrudEditPrevented( 'spamblacklist', [ 'spamblacklist' => [ 'matches' => [ 'example.com' ] ] ] ),
			new EditPrevented( 'spamblacklist', [ 'spamblacklist' => [ 'matches' => [ 'example.com' ] ] ] ),
		];
	}
}
