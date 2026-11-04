<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\PatchLexeme;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\JsonDiffJsonPatchValidator;
use Wikibase\Lexeme\DataAccess\JsonDiffLexemeJsonPatcher;
use Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\PatchLexemeEditSummary;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexeme;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeRequest;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @covers \Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexeme
 *
 * @license GPL-2.0-or-later
 */
class PatchLexemeTest extends MediaWikiUnitTestCase {

	public function testExecutePatchesLemmas(): void {
		$lexemeId = new LexemeId( 'L1' );
		$tags = [ 'some tag' ];
		$userComment = 'user comment';
		$readModelConverter = new LexemeReadModelConverter( $this->createStub( StatementReadModelConverter::class ) );

		$originalLexeme = new LexemeWriteModel(
			$lexemeId,
			new TermList( [ new Term( 'en', 'color' ) ] ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' )
		);
		$lexemeRetriever = $this->createStub( LexemeRetriever::class );
		$lexemeRetriever->method( 'getLexeme' )->willReturn( $readModelConverter->convert( $originalLexeme ) );
		$lexemeSerializer = $this->createStub( LexemeSerializer::class );
		$lexemeSerializer->method( 'serialize' )->willReturn( [ 'lemmas' => [ 'en' => 'color' ] ] );

		$lexemeToUpdate = $originalLexeme->copy();
		$writeModelRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$writeModelRetriever->method( 'getLexemeWriteModel' )->willReturn( $lexemeToUpdate );

		$expectedRevisionId = 123;
		$expectedLastModified = '20250101120000';
		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $lexemeToUpdate, new EditMetadata( $tags, true, new PatchLexemeEditSummary( $userComment ) ) )
			->willReturnCallback( static fn ( LexemeWriteModel $l ) => new LexemeRevision(
				$readModelConverter->convert( $l ),
				$expectedRevisionId,
				$expectedLastModified,
			) );

		$response = ( new PatchLexeme(
			$lexemeRetriever,
			$lexemeSerializer,
			new JsonDiffLexemeJsonPatcher( new JsonDiffJsonPatcher() ),
			$writeModelRetriever,
			$lexemeUpdater,
			new PatchLexemeValidator( new LexemeIdValidator(), new JsonDiffJsonPatchValidator() ),
		) )->execute( new PatchLexemeRequest(
			'L1',
			[
				[ 'op' => 'replace', 'path' => '/lemmas/en', 'value' => 'colour' ],
				[ 'op' => 'add', 'path' => '/lemmas/en-gb', 'value' => 'colour' ],
			],
			$tags,
			true,
			$userComment,
		) );

		$this->assertSame(
			[ 'en' => 'colour', 'en-gb' => 'colour' ],
			$lexemeToUpdate->getLemmas()->toTextArray()
		);
		$this->assertSame( 'colour', $response->lexeme->lemmas['en-gb']->text );
		$this->assertSame( $expectedRevisionId, $response->revisionId );
		$this->assertSame( $expectedLastModified, $response->lastModified );
	}

	public function testGivenInvalidRequest_throwsWithoutUpdating(): void {
		$request = new PatchLexemeRequest( 'L1', [], [], false, null );
		$expectedException = $this->createStub( UseCaseError::class );

		$validator = $this->createMock( PatchLexemeValidator::class );
		$validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request )
			->willThrowException( $expectedException );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		try {
			( new PatchLexeme(
				$this->createStub( LexemeRetriever::class ),
				$this->createStub( LexemeSerializer::class ),
				new JsonDiffLexemeJsonPatcher( new JsonDiffJsonPatcher() ),
				$this->createStub( LexemeWriteModelRetriever::class ),
				$lexemeUpdater,
				$validator,
			) )->execute( $request );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

}
