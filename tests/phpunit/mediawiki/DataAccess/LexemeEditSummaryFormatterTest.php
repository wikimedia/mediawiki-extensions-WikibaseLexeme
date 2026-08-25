<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\DataAccess;

use LogicException;
use MediaWikiLangTestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\CrudEditSummaryAdapter;
use Wikibase\Lexeme\DataAccess\LexemeEditSummaryFormatter;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Repo\Domains\Crud\Domain\Model\LabelEditSummary;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\DataAccess\LexemeEditSummaryFormatter
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0-or-later
 */
class LexemeEditSummaryFormatterTest extends MediaWikiLangTestCase {

	public function testGivenCreateLexemeAction_formatsLikeWbeditentity(): void {
		$this->assertSame(
			'/* wbeditentity-create-lexeme:0| */',
			$this->newFormatter()->format(
				new CrudEditSummaryAdapter( EditSummaryAction::CREATE_LEXEME, null ),
			),
		);
	}

	public function testGivenCreateLexemeActionWithUserComment_appendsComment(): void {
		$this->assertSame(
			'/* wbeditentity-create-lexeme:0| */ user comment',
			$this->newFormatter()->format(
				new CrudEditSummaryAdapter( EditSummaryAction::CREATE_LEXEME, 'user comment' ),
			),
		);
	}

	public function testGivenSummaryOfOtherEntityType_throws(): void {
		$this->expectException( LogicException::class );

		$this->newFormatter()->format(
			LabelEditSummary::newAddSummary( 'user comment', new Term( 'en', 'LABEL-TEXT' ) ),
		);
	}

	private function newFormatter(): LexemeEditSummaryFormatter {
		return new LexemeEditSummaryFormatter( WikibaseRepo::getSummaryFormatter() );
	}

}
