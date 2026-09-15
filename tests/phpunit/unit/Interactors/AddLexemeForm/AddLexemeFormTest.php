<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeForm;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\Store\LexemeReadModelConverter;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeForm;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormRequest;
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

		$response = ( new AddLexemeForm( $lexemeRetriever, $lexemeUpdater ) )->execute( $request );

		$this->assertSame( "{$lexemeId}-F1", $response->form->id->getSerialization() );
		$this->assertSame( $formRepresentation, $response->form->representations['en']->text );
		$this->assertEquals( new GrammaticalFeatures( $grammaticalFeature ), $response->form->grammaticalFeatures );
		$this->assertSame( $expectedRevisionId, $response->revisionId );
		$this->assertSame( $expectedLastModified, $response->lastModified );
	}

}
