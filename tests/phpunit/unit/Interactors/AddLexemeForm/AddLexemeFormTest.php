<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeForm;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\ReadModel\LatestLexemeRevisionMetadataResult;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeForm;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormRequest;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeForm
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeFormTest extends MediaWikiUnitTestCase {

	public function testExecuteAddsForm(): void {
		$lexemeId = new LexemeId( 'L1' );
		$grammaticalFeature = new ItemId( 'Q123' );
		$formRepresentation = 'potatoes';
		$request = new AddLexemeFormRequest(
			'L1',
			[
				'representations' => [ 'en' => $formRepresentation ],
				'grammatical_features' => [ $grammaticalFeature->getSerialization() ],
			],
			[ 'some tag' ],
			true,
			'user comment',
		);

		$form = new BlankForm();
		$form->setRepresentations( new TermList( [ new Term( 'en', $formRepresentation ) ] ) );
		$form->setGrammaticalFeatures( [ $grammaticalFeature ] );

		$validator = $this->createMock( AddLexemeFormValidator::class );
		$validator->method( 'getValidatedForm' )->willReturn( $form );

		$lexeme = new LexemeWriteModel( $lexemeId, new TermList(), new ItemId( 'Q1' ), new ItemId( 'Q2' ) );
		$lexemeRetriever = $this->createStub( LexemeWriteModelRetriever::class );
		$lexemeRetriever->method( 'getLexemeWriteModel' )->willReturn( $lexeme );

		$expectedRevisionId = 123;
		$expectedLastModified = '20250101120000';
		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $lexeme )
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

		$this->assertSame( "{$lexemeId}-F1", $response->form->id->getSerialization() );
		$this->assertSame( $formRepresentation, $response->form->representations['en']->text );
		$this->assertEquals( new GrammaticalFeatures( $grammaticalFeature ), $response->form->grammaticalFeatures );
		$this->assertSame( $expectedRevisionId, $response->revisionId );
		$this->assertSame( $expectedLastModified, $response->lastModified );
	}

	public function testGivenInvalidRequest_throwsWithoutUpdating(): void {
		$request = new AddLexemeFormRequest( 'L1', [], [], false, null );
		$expectedException = $this->createStub( UseCaseError::class );

		$validator = $this->createMock( AddLexemeFormValidator::class );
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

	public function testGivenLexemeNotFound_throwsWithoutUpdating(): void {
		$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestLexemeRevisionMetadataResult::lexemeNotFound() );

		$lexemeRetriever = $this->createMock( LexemeWriteModelRetriever::class );
		$lexemeRetriever->expects( $this->never() )->method( 'getLexemeWriteModel' );

		$lexemeUpdater = $this->createMock( LexemeUpdater::class );
		$lexemeUpdater->expects( $this->never() )->method( 'update' );

		try {
			$this->newUseCase(
				lexemeRetriever: $lexemeRetriever,
				lexemeUpdater: $lexemeUpdater,
				metadataRetriever: $metadataRetriever,
			)->execute( new AddLexemeFormRequest( 'L999', [], [], false, null ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::RESOURCE_NOT_FOUND, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_RESOURCE_TYPE => 'lexeme' ], $e->context );
		}
	}

	private function newUseCase(
		?LexemeWriteModelRetriever $lexemeRetriever = null,
		?LexemeUpdater $lexemeUpdater = null,
		?AddLexemeFormValidator $validator = null,
		?LexemeRevisionMetadataRetriever $metadataRetriever = null,
	): AddLexemeForm {
		if ( $metadataRetriever === null ) {
			$metadataRetriever = $this->createStub( LexemeRevisionMetadataRetriever::class );
			$metadataRetriever->method( 'getLatestRevisionMetadata' )
				->willReturn( LatestLexemeRevisionMetadataResult::concreteRevision( 1, '20260925070707' ) );
		}

		return new AddLexemeForm(
			$lexemeRetriever ?? $this->createStub( LexemeWriteModelRetriever::class ),
			$lexemeUpdater ?? $this->createStub( LexemeUpdater::class ),
			$validator ?? $this->createStub( AddLexemeFormValidator::class ),
			$metadataRetriever,
		);
	}

}
