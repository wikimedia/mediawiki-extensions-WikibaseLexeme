<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataAccess;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\JsonDiffLexemeJsonPatcher;
use Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatcher;

/**
 * @covers \Wikibase\Lexeme\DataAccess\JsonDiffLexemeJsonPatcher
 *
 * @license GPL-2.0-or-later
 */
class JsonDiffLexemeJsonPatcherTest extends TestCase {

	public function testPatchesSerializationContainingArrayObjects(): void {
		$patcher = new JsonDiffLexemeJsonPatcher( new JsonDiffJsonPatcher() );

		$this->assertSame(
			[
				'lemmas' => [ 'en' => 'colour', 'en-gb' => 'colour' ],
				'statements' => [],
			],
			$patcher->patch(
				[
					'lemmas' => new ArrayObject( [ 'en' => 'color' ] ),
					'statements' => new ArrayObject(),
				],
				[
					[ 'op' => 'replace', 'path' => '/lemmas/en', 'value' => 'colour' ],
					[ 'op' => 'add', 'path' => '/lemmas/en-gb', 'value' => 'colour' ],
				]
			)
		);
	}

}
