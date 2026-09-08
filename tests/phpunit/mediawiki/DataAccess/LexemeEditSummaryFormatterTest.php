<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\DataAccess;

use Generator;
use LogicException;
use MediaWikiLangTestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\DataAccess\CrudEditSummaryAdapter;
use Wikibase\Lexeme\DataAccess\LexemeEditSummaryFormatter;
use Wikibase\Lexeme\Domain\Model\AddStatementEditSummary;
use Wikibase\Lexeme\Domain\Model\CreateLexemeEditSummary;
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

	/**
	 * @dataProvider editSummaryProvider
	 */
	public function testFormat( CrudEditSummaryAdapter $editSummary, string $formattedSummary ): void {
		$this->assertSame( $formattedSummary, $this->newFormatter()->format( $editSummary ) );
	}

	public static function editSummaryProvider(): Generator {
		yield 'create lexeme' => [
			new CrudEditSummaryAdapter( new CreateLexemeEditSummary( null ) ),
			'/* wbeditentity-create-lexeme:0| */',
		];

		yield 'create lexeme with user comment' => [
			new CrudEditSummaryAdapter( new CreateLexemeEditSummary( 'user comment' ) ),
			'/* wbeditentity-create-lexeme:0| */ user comment',
		];

		yield 'add statement' => [
			new CrudEditSummaryAdapter(
				new AddStatementEditSummary( null, NewStatement::noValueFor( 'P123' )->build() ),
			),
			'/* wbsetclaim-create:1||1 */ [[Property:P123]]: no value',
		];

		yield 'add statement with user comment' => [
			new CrudEditSummaryAdapter(
				new AddStatementEditSummary( 'user comment', NewStatement::noValueFor( 'P123' )->build() ),
			),
			'/* wbsetclaim-create:1||1 */ [[Property:P123]]: no value, user comment',
		];
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
