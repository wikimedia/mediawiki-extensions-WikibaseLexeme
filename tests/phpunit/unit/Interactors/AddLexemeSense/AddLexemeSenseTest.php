<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeSense;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\AddSenseEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSense;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseRequest;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSense
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseTest extends MediaWikiUnitTestCase {

	public function testExecuteAddsSense(): void {
		$lexemeId = new LexemeId( 'L1' );
		$gloss = 'a starchy tuber';
		$tags = [ 'some tag' ];
		$userComment = 'user comment';
		$request = new AddLexemeSenseRequest(
			'L1',
			[ 'glosses' => [ 'en' => $gloss ] ],
			$tags,
			true,
			$userComment,
		);

		$sense = new BlankSense();
		$sense->getGlosses()->setTerm( new Term( 'en', $gloss ) );
		$validator = $this->createStub( AddLexemeSenseValidator::class );
		$validator->method( 'getValidatedLexemeId' )->willReturn( $lexemeId );
		$validator->method( 'getValidatedSense' )->willReturn( $sense );

		$lexeme = new LexemeWriteModel( $lexemeId, new TermList(), new ItemId( 'Q1' ), new ItemId( 'Q2' ) );
		$lexemeRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$lexemeRetriever->method( 'getLexemeWriteModel' )->willReturn( $lexeme );

		$expectedRevisionId = 123;
		$expectedLastModified = '20250101120000';
		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $lexeme, new EditMetadata( $tags, true, new AddSenseEditSummary( $userComment ) ) )
			->willReturnCallback( fn ( LexemeWriteModel $l ) => new LexemeRevision(
				( new LexemeReadModelConverter( $this->createStub( StatementReadModelConverter::class ) ) )
					->convert( $l ),
				$expectedRevisionId,
				$expectedLastModified,
			) );

		$response = $this->newUseCase(
			lexemeRetriever: $lexemeRetriever,
			lexemeUpdater: $lexemeUpdater,
			validator: $validator,
		)->execute( $request );

		$this->assertSame( "{$lexemeId}-S1", $response->sense->id->getSerialization() );
		$this->assertSame( $gloss, $response->sense->glosses['en']->text );
		$this->assertSame( $expectedRevisionId, $response->revisionId );
		$this->assertSame( $expectedLastModified, $response->lastModified );
	}

	public function testGivenInvalidRequest_throwsWithoutUpdating(): void {
		$request = new AddLexemeSenseRequest( 'L1', [], [], false, 'user comment' );
		$expectedException = $this->createStub( UseCaseError::class );

		$validator = $this->createMock( AddLexemeSenseValidator::class );
		$validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request )
			->willThrowException( $expectedException );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		try {
			$this->newUseCase( lexemeUpdater: $lexemeUpdater, validator: $validator )
				->execute( $request );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(
		?LexemeWriteModelRetriever $lexemeRetriever = null,
		?LexemeUpdater $lexemeUpdater = null,
		?AddLexemeSenseValidator $validator = null,
	): AddLexemeSense {
		return new AddLexemeSense(
			$lexemeRetriever ?? $this->createStub( LexemeWriteModelRetriever::class ),
			$lexemeUpdater ?? $this->createStub( LexemeUpdater::class ),
			$validator ?? $this->createStub( AddLexemeSenseValidator::class ),
		);
	}

}
